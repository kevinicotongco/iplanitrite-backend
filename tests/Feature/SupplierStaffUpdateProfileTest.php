<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierRole;
use App\Models\SupplierStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierStaffUpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;
    private SupplierRole $supplierRole;
    private SupplierStaff $supplierStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $country = $this->createTestCountry();
        $this->supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Supplier',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Test Description',
            'country_id' => $country->id,
            'timezone' => 'UTC',
        ]);

        $this->supplierRole = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Manager',
        ]);

        $this->supplierStaff = SupplierStaff::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'supplier_role_id' => $this->supplierRole->id,
            'email' => 'staff@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);
    }

    public function test_supplier_staff_can_update_profile(): void
    {
        $response = $this->actingAs($this->supplierStaff, 'supplier_staff')
            ->putJson('/api/supplier_staff/profile', [
                'firstName' => 'Updated',
                'middleName' => 'Middle',
                'lastName' => 'Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'supplierId',
                'supplierRoleId',
                'email',
                'firstName',
                'middleName',
                'lastName',
                'profilePicture',
                'address',
                'contactNumber',
            ])
            ->assertJson([
                'firstName' => 'Updated',
                'middleName' => 'Middle',
                'lastName' => 'Name',
            ]);

        $this->assertDatabaseHas('supplier_staff', [
            'id' => $this->supplierStaff->id,
            'first_name' => 'Updated',
            'middle_name' => 'Middle',
            'last_name' => 'Name',
        ]);
    }

    public function test_supplier_staff_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->supplierStaff, 'supplier_staff')
            ->putJson('/api/supplier_staff/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['firstName', 'lastName']);
    }

    public function test_unauthenticated_user_cannot_update_supplier_staff_profile(): void
    {
        $response = $this->putJson('/api/supplier_staff/profile', [
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]);

        $response->assertStatus(401);
    }
}
