<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\SupplierRole;
use App\Models\SupplierStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierStaffProfileTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;
    private SupplierRole $supplierRole;
    private SupplierStaff $supplierStaff;
    private Admin $admin;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $country = $this->createTestCountry();

        $this->supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Supplier',
            'description' => 'Test Description',
            'status' => 'Active',
            'subscription_tier' => 'Free',
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

        $this->admin = Admin::create([
            'id' => Str::uuid()->toString(),
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Admin',
        ]);

        $this->client = Client::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'email' => 'client@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Client',
        ]);
    }

    public function test_supplier_staff_can_view_profile(): void
    {
        $response = $this->actingAs($this->supplierStaff, 'supplier_staff')
            ->getJson('/api/supplier-staff/profile');

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
                'id' => $this->supplierStaff->id,
                'email' => 'staff@test.com',
                'firstName' => 'Test',
                'lastName' => 'Staff',
            ]);
    }

    public function test_unauthenticated_user_cannot_view_supplier_staff_profile(): void
    {
        $response = $this->getJson('/api/supplier-staff/profile');

        $response->assertStatus(401);
    }

    public function test_admin_cannot_view_supplier_staff_profile(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/supplier-staff/profile');

        $response->assertStatus(401);
    }

    public function test_client_cannot_view_supplier_staff_profile(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->getJson('/api/supplier-staff/profile');

        $response->assertStatus(401);
    }
}
