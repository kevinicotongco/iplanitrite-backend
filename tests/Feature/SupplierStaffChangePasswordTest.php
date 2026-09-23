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

class SupplierStaffChangePasswordTest extends TestCase
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

    public function test_supplier_staff_can_change_password(): void
    {
        $response = $this->actingAs($this->supplierStaff, 'supplier_staff')
            ->putJson('/api/supplier-staff/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(204);

        $this->supplierStaff->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->supplierStaff->password));
    }

    public function test_supplier_staff_change_password_validates_current_password(): void
    {
        $response = $this->actingAs($this->supplierStaff, 'supplier_staff')
            ->putJson('/api/supplier-staff/password', [
                'currentPassword' => 'wrongpassword',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currentPassword']);
    }

    public function test_supplier_staff_change_password_validates_new_password_confirmation(): void
    {
        $response = $this->actingAs($this->supplierStaff, 'supplier_staff')
            ->putJson('/api/supplier-staff/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'differentpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }

    public function test_supplier_staff_change_password_validates_minimum_length(): void
    {
        $response = $this->actingAs($this->supplierStaff, 'supplier_staff')
            ->putJson('/api/supplier-staff/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'short',
                'newPasswordConfirmation' => 'short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }
}
