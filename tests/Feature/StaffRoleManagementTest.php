<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRolePermissionEnum;
use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use App\Models\Country;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\AccountRolePermission;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private Account $account;
    private Country $country;
    private AccountRole $role;
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

        $this->account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $this->role = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Admin',
        ]);

        $this->staff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'staff@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $this->token = $this->staff->createToken('staff-token')->plainTextToken;
    }

    // GET /api/staff/roles Tests

    public function test_staff_can_get_roles_list(): void
    {
        AccountRolePermission::create([
            'id' => Str::uuid()->toString(),
            'account_role_id' => $this->role->id,
            'permission' => AccountRolePermissionEnum::StaffList,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        AccountRolePermission::create([
            'id' => Str::uuid()->toString(),
            'account_role_id' => $this->role->id,
            'permission' => AccountRolePermissionEnum::StaffCreate,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/staff/roles');

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

    public function test_roles_list_only_shows_roles_for_account(): void
    {
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $otherRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Other Role',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/staff/roles');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonMissing(['name' => 'Other Role']);
    }

    public function test_unauthenticated_user_cannot_get_roles(): void
    {
        $response = $this->getJson('/api/staff/roles');

        $response->assertStatus(401);
    }

    // POST /api/staff/roles Tests

    public function test_staff_can_create_role(): void
    {
        $roleData = [
            'name' => 'Manager',
            'permissions' => [
                AccountRolePermissionEnum::EventList->value,
                AccountRolePermissionEnum::EventCreate->value,
                AccountRolePermissionEnum::EventUpdate->value,
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/roles', $roleData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('account_roles', [
            'account_id' => $this->account->id,
            'name' => 'Manager',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $role = AccountRole::where('name', 'Manager')
            ->where('account_id', $this->account->id)
            ->first();

        $this->assertNotNull($role);
        $this->assertCount(3, $role->permissions);

        $this->assertDatabaseHas('account_role_permissions', [
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::EventList->value,
        ]);

        $this->assertDatabaseHas('account_role_permissions', [
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::EventCreate->value,
        ]);

        $this->assertDatabaseHas('account_role_permissions', [
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::EventUpdate->value,
        ]);
    }

    public function test_create_role_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/staff/roles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'permissions']);
    }

    public function test_create_role_validates_permissions_array_not_empty(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/staff/roles', [
                'name' => 'Test Role',
                'permissions' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    public function test_create_role_validates_permission_values(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/staff/roles', [
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
            'permissions' => [AccountRolePermissionEnum::EventList->value],
        ];

        $response = $this->postJson('/api/staff/roles', $roleData);

        $response->assertStatus(401);
    }

    // PUT /api/staff/roles/{id} Tests

    public function test_staff_can_update_role(): void
    {
        $role = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Old Name',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        AccountRolePermission::create([
            'id' => Str::uuid()->toString(),
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::StaffList,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'permissions' => [
                AccountRolePermissionEnum::EventList->value,
                AccountRolePermissionEnum::EventCreate->value,
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/roles/{$role->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_roles', [
            'id' => $role->id,
            'name' => 'Updated Name',
            'updated_by' => $this->staff->id,
        ]);

        // Old permission should be deleted
        $this->assertDatabaseMissing('account_role_permissions', [
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::StaffList->value,
            'deleted_at' => null,
        ]);

        // New permissions should be created
        $this->assertDatabaseHas('account_role_permissions', [
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::EventList->value,
        ]);

        $this->assertDatabaseHas('account_role_permissions', [
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::EventCreate->value,
        ]);
    }

    public function test_cannot_update_role_from_another_account(): void
    {
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $otherRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Other Role',
        ]);

        $updateData = [
            'name' => 'Hacked Name',
            'permissions' => [AccountRolePermissionEnum::EventList->value],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/roles/{$otherRole->id}", $updateData);

        $response->assertStatus(404);
    }

    public function test_update_role_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->putJson("/api/staff/roles/{$this->role->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'permissions']);
    }

    public function test_unauthenticated_user_cannot_update_role(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'permissions' => [AccountRolePermissionEnum::EventList->value],
        ];

        $response = $this->putJson("/api/staff/roles/{$this->role->id}", $updateData);

        $response->assertStatus(401);
    }

    // DELETE /api/staff/roles/{id} Tests

    public function test_staff_can_delete_role(): void
    {
        $role = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'To Delete',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        AccountRolePermission::create([
            'id' => Str::uuid()->toString(),
            'account_role_id' => $role->id,
            'permission' => AccountRolePermissionEnum::StaffList,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/staff/roles/{$role->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('account_roles', [
            'id' => $role->id,
        ]);

        $this->assertSoftDeleted('account_role_permissions', [
            'account_role_id' => $role->id,
        ]);
    }

    public function test_cannot_delete_role_from_another_account(): void
    {
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $otherRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Other Role',
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/staff/roles/{$otherRole->id}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_role(): void
    {
        $response = $this->deleteJson("/api/staff/roles/{$this->role->id}");

        $response->assertStatus(401);
    }
}
