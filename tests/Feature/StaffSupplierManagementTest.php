<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Address;
use App\Models\ContactNumber;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffSupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private Staff $staff;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $country = $this->createTestCountry();

        $this->account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Test Description',
            'country_id' => $country->id,
            'timezone' => 'UTC',
        ]);

        $accountRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Manager',
        ]);

        $this->staff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $accountRole->id,
            'email' => 'staff@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $this->token = $this->staff->createToken('test-token')->plainTextToken;
    }

    public function test_staff_can_get_all_suppliers(): void
    {
        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->account->country_id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->account->country_id,
        ]);

        // Create supplier without observers to avoid auth requirements
        Supplier::withoutEvents(function () use ($contactNumber, $address) {
            return Supplier::create([
                'id' => Str::uuid()->toString(),
                'account_id' => $this->account->id,
                'company_name' => 'Test Supplier',
                'contact_person' => 'John Doe',
                'contact_number_id' => $contactNumber->id,
                'address_id' => $address->id,
            ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/staff/suppliers');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'companyName',
                    'contactPerson',
                    'contactNumber',
                    'address',
                ],
            ]);
    }

    public function test_staff_can_get_single_supplier(): void
    {
        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->account->country_id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->account->country_id,
        ]);

        $supplier = Supplier::withoutEvents(function () use ($contactNumber, $address) {
            return Supplier::create([
                'id' => Str::uuid()->toString(),
                'account_id' => $this->account->id,
                'company_name' => 'Test Supplier',
                'contact_person' => 'John Doe',
                'contact_number_id' => $contactNumber->id,
                'address_id' => $address->id,
            ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/staff/suppliers/' . $supplier->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $supplier->id,
                'companyName' => 'Test Supplier',
                'contactPerson' => 'John Doe',
            ]);
    }

    public function test_staff_can_create_supplier(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/suppliers', [
                'companyName' => 'New Supplier',
                'contactPerson' => 'Jane Smith',
                'contactNumber' => '+1987654321',
                'address' => [
                    'line1' => '456 Oak Ave',
                    'line2' => 'Suite 100',
                    'city' => 'New City',
                    'state' => 'New State',
                    'zip' => '54321',
                    'lat' => '40.7128',
                    'long' => '-74.0060',
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('suppliers', [
            'account_id' => $this->account->id,
            'company_name' => 'New Supplier',
            'contact_person' => 'Jane Smith',
        ]);

        // Verify audit log was created
        $supplier = Supplier::where('company_name', 'New Supplier')->first();
        $this->assertDatabaseHas('supplier_logs', [
            'supplier_id' => $supplier->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Create->value,
        ]);
    }

    public function test_staff_can_update_supplier(): void
    {
        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->account->country_id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->account->country_id,
        ]);

        $supplier = Supplier::withoutEvents(function () use ($contactNumber, $address) {
            return Supplier::create([
                'id' => Str::uuid()->toString(),
                'account_id' => $this->account->id,
                'company_name' => 'Test Supplier',
                'contact_person' => 'John Doe',
                'contact_number_id' => $contactNumber->id,
                'address_id' => $address->id,
            ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/suppliers/' . $supplier->id, [
                'companyName' => 'Updated Supplier',
                'contactPerson' => 'Updated Person',
                'contactNumber' => '+1111111111',
                'address' => [
                    'line1' => 'Updated Street',
                    'city' => 'Updated City',
                    'state' => 'Updated State',
                    'zip' => '99999',
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'company_name' => 'Updated Supplier',
            'contact_person' => 'Updated Person',
        ]);

        // Verify audit log was created for update
        $this->assertDatabaseHas('supplier_logs', [
            'supplier_id' => $supplier->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_staff_can_delete_supplier(): void
    {
        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->account->country_id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->account->country_id,
        ]);

        $supplier = Supplier::withoutEvents(function () use ($contactNumber, $address) {
            return Supplier::create([
                'id' => Str::uuid()->toString(),
                'account_id' => $this->account->id,
                'company_name' => 'Test Supplier',
                'contact_person' => 'John Doe',
                'contact_number_id' => $contactNumber->id,
                'address_id' => $address->id,
            ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/staff/suppliers/' . $supplier->id);

        $response->assertStatus(200);

        $this->assertSoftDeleted('suppliers', [
            'id' => $supplier->id,
        ]);

        // Verify audit log was created for delete
        $this->assertDatabaseHas('supplier_logs', [
            'supplier_id' => $supplier->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Delete->value,
        ]);
    }

    public function test_create_supplier_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/suppliers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['companyName', 'contactPerson', 'contactNumber', 'address']);
    }

    public function test_unauthenticated_staff_cannot_access_suppliers(): void
    {
        $response = $this->getJson('/api/staff/suppliers');
        $response->assertStatus(401);
    }

    public function test_staff_cannot_view_supplier_from_other_account(): void
    {
        // Create another account with a supplier
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Other Description',
            'country_id' => $this->account->country_id,
            'timezone' => 'UTC',
        ]);

        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->account->country_id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->account->country_id,
        ]);

        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'company_name' => 'Other Supplier',
            'contact_person' => 'Jane Doe',
            'contact_number_id' => $contactNumber->id,
            'address_id' => $address->id,
        ]);

        // Try to view the other account's supplier
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/staff/suppliers/' . $otherSupplier->id);

        $response->assertStatus(404);
    }

    public function test_staff_cannot_update_supplier_from_other_account(): void
    {
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Other Description',
            'country_id' => $this->account->country_id,
            'timezone' => 'UTC',
        ]);

        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->account->country_id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->account->country_id,
        ]);

        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'company_name' => 'Other Supplier',
            'contact_person' => 'Jane Doe',
            'contact_number_id' => $contactNumber->id,
            'address_id' => $address->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/suppliers/' . $otherSupplier->id, [
                'companyName' => 'Hacked Name',
                'contactPerson' => 'Hacker',
                'contactNumber' => '+1111111111',
                'address' => [
                    'line1' => 'Hack Street',
                    'city' => 'Hack City',
                    'state' => 'Hack State',
                    'zip' => '99999',
                ],
            ]);

        $response->assertStatus(404);
    }

    public function test_staff_cannot_delete_supplier_from_other_account(): void
    {
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Other Description',
            'country_id' => $this->account->country_id,
            'timezone' => 'UTC',
        ]);

        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->account->country_id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->account->country_id,
        ]);

        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'company_name' => 'Other Supplier',
            'contact_person' => 'Jane Doe',
            'contact_number_id' => $contactNumber->id,
            'address_id' => $address->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/staff/suppliers/' . $otherSupplier->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('suppliers', [
            'id' => $otherSupplier->id,
            'deleted_at' => null,
        ]);
    }
}
