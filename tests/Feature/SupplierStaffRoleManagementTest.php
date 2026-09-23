<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupplierRolePermissionEnum;
use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use App\Models\Country;
use App\Models\Supplier;
use App\Models\SupplierRole;
use App\Models\SupplierRolePermission;
use App\Models\SupplierStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierStaffRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private SupplierStaff $staff;
    private Supplier $supplier;
    private Country $country;
    private SupplierRole $role;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::create([
            'id' => Str::uuid()->toString(),
            'name' => 'United States',
            'iso2_code' => 'US',
            'iso3_code' => 'USA',
            'language_locale' => 'en-US',
            'calling_code' => '+1',
            'flag' => '🇺🇸',
            'currency_code' => 'USD',
            'currency_name' => 'US Dollar',
            'currency_symbol' => '$',
        ]);

        $this->supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $this->role = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Admin',
        ]);

        $this->staff = SupplierStaff::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'supplier_role_id' => $this->role->id,
            'email' => 'staff@supplier.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $this->token = $this->staff->createToken('staff-token')->plainTextToken;
    }

    // GET /api/supplier-staff/roles Tests

    public function test_supplier_staff_can_get_roles_list(): void
    {
        SupplierRolePermission::create([
            'id' => Str::uuid()->toString(),
            'supplier_role_id' => $this->role->id,
            'permission' => SupplierRolePermissionEnum::StaffList,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierRolePermission::create([
            'id' => Str::uuid()->toString(),
            'supplier_role_id' => $this->role->id,
            'permission' => SupplierRolePermissionEnum::StaffCreate,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/supplier-staff/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'permissions',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'Admin',
            ])
            ->assertJsonPath('0.permissions', function ($permissions) {
                return count($permissions) === 2 &&
                    in_array('StaffList', $permissions) &&
                    in_array('StaffCreate', $permissions);
            });
    }

    public function test_roles_list_only_shows_roles_for_supplier(): void
    {
        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $otherRole = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $otherSupplier->id,
            'name' => 'Other Role',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/supplier-staff/roles');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonMissing(['name' => 'Other Role']);
    }

    public function test_unauthenticated_user_cannot_get_roles(): void
    {
        $response = $this->getJson('/api/supplier-staff/roles');

        $response->assertStatus(401);
    }

    // POST /api/supplier-staff/roles Tests

    public function test_supplier_staff_can_create_role(): void
    {
        $roleData = [
            'name' => 'Manager',
            'permissions' => [
                SupplierRolePermissionEnum::EventList->value,
                SupplierRolePermissionEnum::EventCreate->value,
                SupplierRolePermissionEnum::EventUpdate->value,
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/supplier-staff/roles', $roleData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('supplier_roles', [
            'supplier_id' => $this->supplier->id,
            'name' => 'Manager',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $role = SupplierRole::where('name', 'Manager')
            ->where('supplier_id', $this->supplier->id)
            ->first();

        $this->assertNotNull($role);
        $this->assertCount(3, $role->permissions);

        $this->assertDatabaseHas('supplier_role_permissions', [
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::EventList->value,
        ]);

        $this->assertDatabaseHas('supplier_role_permissions', [
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::EventCreate->value,
        ]);

        $this->assertDatabaseHas('supplier_role_permissions', [
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::EventUpdate->value,
        ]);
    }

    public function test_create_role_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/supplier-staff/roles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'permissions']);
    }

    public function test_create_role_validates_permissions_array_not_empty(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/supplier-staff/roles', [
                'name' => 'Test Role',
                'permissions' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    public function test_create_role_validates_permission_values(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/supplier-staff/roles', [
                'name' => 'Test Role',
                'permissions' => ['InvalidPermission'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permissions.0']);
    }

    public function test_unauthenticated_user_cannot_create_role(): void
    {
        $roleData = [
            'name' => 'Manager',
            'permissions' => [SupplierRolePermissionEnum::EventList->value],
        ];

        $response = $this->postJson('/api/supplier-staff/roles', $roleData);

        $response->assertStatus(401);
    }

    // PUT /api/supplier-staff/roles/{id} Tests

    public function test_supplier_staff_can_update_role(): void
    {
        $role = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Old Name',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierRolePermission::create([
            'id' => Str::uuid()->toString(),
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::StaffList,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'permissions' => [
                SupplierRolePermissionEnum::EventList->value,
                SupplierRolePermissionEnum::EventCreate->value,
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/supplier-staff/roles/{$role->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('supplier_roles', [
            'id' => $role->id,
            'name' => 'Updated Name',
            'updated_by' => $this->staff->id,
        ]);

        // Old permission should be deleted
        $this->assertDatabaseMissing('supplier_role_permissions', [
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::StaffList->value,
            'deleted_at' => null,
        ]);

        // New permissions should be created
        $this->assertDatabaseHas('supplier_role_permissions', [
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::EventList->value,
        ]);

        $this->assertDatabaseHas('supplier_role_permissions', [
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::EventCreate->value,
        ]);
    }

    public function test_cannot_update_role_from_another_supplier(): void
    {
        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $otherRole = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $otherSupplier->id,
            'name' => 'Other Role',
        ]);

        $updateData = [
            'name' => 'Hacked Name',
            'permissions' => [SupplierRolePermissionEnum::EventList->value],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/supplier-staff/roles/{$otherRole->id}", $updateData);

        $response->assertStatus(404);
    }

    public function test_update_role_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->putJson("/api/supplier-staff/roles/{$this->role->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'permissions']);
    }

    public function test_unauthenticated_user_cannot_update_role(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'permissions' => [SupplierRolePermissionEnum::EventList->value],
        ];

        $response = $this->putJson("/api/supplier-staff/roles/{$this->role->id}", $updateData);

        $response->assertStatus(401);
    }

    // DELETE /api/supplier-staff/roles/{id} Tests

    public function test_supplier_staff_can_delete_role(): void
    {
        $role = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'To Delete',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierRolePermission::create([
            'id' => Str::uuid()->toString(),
            'supplier_role_id' => $role->id,
            'permission' => SupplierRolePermissionEnum::StaffList,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/supplier-staff/roles/{$role->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('supplier_roles', [
            'id' => $role->id,
        ]);

        $this->assertSoftDeleted('supplier_role_permissions', [
            'supplier_role_id' => $role->id,
        ]);
    }

    public function test_cannot_delete_role_from_another_supplier(): void
    {
        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $otherRole = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $otherSupplier->id,
            'name' => 'Other Role',
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/supplier-staff/roles/{$otherRole->id}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_role(): void
    {
        $response = $this->deleteJson("/api/supplier-staff/roles/{$this->role->id}");

        $response->assertStatus(401);
    }
}
