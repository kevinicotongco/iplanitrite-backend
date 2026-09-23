<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use App\Models\Country;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffManagementTest extends TestCase
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
            'email' => 'admin@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        $this->token = $this->staff->createToken('staff-token')->plainTextToken;
    }

    // GET /api/staff/staff Tests

    public function test_staff_can_get_staff_list(): void
    {
        // Create another staff member
        Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'staff@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/staff/staff');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'accountId',
                    'accountRoleId',
                    'email',
                    'firstName',
                    'middleName',
                    'lastName',
                    'profilePicture',
                    'address',
                    'contactNumber',
                ],
            ])
            ->assertJsonCount(2);
    }

    public function test_staff_list_only_shows_staff_for_account(): void
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

        Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'account_role_id' => $otherRole->id,
            'email' => 'other@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Other',
            'last_name' => 'Staff',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/staff/staff');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonMissing(['email' => 'other@account.com']);
    }

    public function test_unauthenticated_user_cannot_get_staff_list(): void
    {
        $response = $this->getJson('/api/staff/staff');

        $response->assertStatus(401);
    }

    // POST /api/staff/staff Tests

    public function test_staff_can_create_staff_with_minimal_data(): void
    {
        $staffData = [
            'email' => 'newstaff@account.com',
            'firstName' => 'New',
            'lastName' => 'Staff',
            'role' => $this->role->id,
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/staff', $staffData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('staff', [
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'newstaff@account.com',
            'first_name' => 'New',
            'last_name' => 'Staff',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        // Verify password was set (should be hashed)
        $newStaff = Staff::where('email', 'newstaff@account.com')->first();
        $this->assertNotNull($newStaff);
        $this->assertNotNull($newStaff->password);
        $this->assertNotEquals('', $newStaff->password);
    }

    public function test_staff_can_create_staff_with_full_data(): void
    {
        $staffData = [
            'email' => 'fulldata@account.com',
            'firstName' => 'Full',
            'middleName' => 'Data',
            'lastName' => 'Staff',
            'contactNumber' => '1234567890',
            'role' => $this->role->id,
            'address' => [
                'line1' => '123 Main St',
                'line2' => 'Apt 4',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'lat' => '40.7128',
                'long' => '-74.0060',
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/staff', $staffData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('staff', [
            'email' => 'fulldata@account.com',
            'first_name' => 'Full',
            'middle_name' => 'Data',
            'last_name' => 'Staff',
        ]);

        $newStaff = Staff::where('email', 'fulldata@account.com')->first();
        $this->assertNotNull($newStaff->address_id);
        $this->assertNotNull($newStaff->contact_number_id);

        $this->assertDatabaseHas('addresses', [
            'id' => $newStaff->address_id,
            'line1' => '123 Main St',
            'city' => 'New York',
        ]);

        $this->assertDatabaseHas('contact_numbers', [
            'id' => $newStaff->contact_number_id,
            'number' => '1234567890',
        ]);
    }

    public function test_create_staff_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/staff/staff', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'firstName', 'lastName', 'role']);
    }

    public function test_create_staff_validates_email_uniqueness(): void
    {
        $staffData = [
            'email' => $this->staff->email, // Already exists
            'firstName' => 'Duplicate',
            'lastName' => 'Email',
            'role' => $this->role->id,
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/staff', $staffData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_staff_validates_role_exists(): void
    {
        $staffData = [
            'email' => 'test@account.com',
            'firstName' => 'Test',
            'lastName' => 'Staff',
            'role' => Str::uuid()->toString(), // Non-existent role
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/staff', $staffData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_create_staff_validates_address_fields_when_provided(): void
    {
        $staffData = [
            'email' => 'test@account.com',
            'firstName' => 'Test',
            'lastName' => 'Staff',
            'role' => $this->role->id,
            'address' => [
                'line1' => '123 Main St',
                // Missing required fields: city, state, zip
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/staff', $staffData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address.city', 'address.state', 'address.zip']);
    }

    public function test_unauthenticated_user_cannot_create_staff(): void
    {
        $staffData = [
            'email' => 'test@account.com',
            'firstName' => 'Test',
            'lastName' => 'Staff',
            'role' => $this->role->id,
        ];

        $response = $this->postJson('/api/staff/staff', $staffData);

        $response->assertStatus(401);
    }

    // PUT /api/staff/staff/{id} Tests

    public function test_staff_can_update_staff(): void
    {
        $staffMember = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'old@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        $updateData = [
            'email' => 'updated@account.com',
            'firstName' => 'Updated',
            'middleName' => 'Middle',
            'lastName' => 'Name',
            'role' => $this->role->id,
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/staff/{$staffMember->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('staff', [
            'id' => $staffMember->id,
            'email' => 'updated@account.com',
            'first_name' => 'Updated',
            'middle_name' => 'Middle',
            'last_name' => 'Name',
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_staff_can_update_staff_address(): void
    {
        $staffMember = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'staff@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $updateData = [
            'email' => 'staff@account.com',
            'firstName' => 'Test',
            'lastName' => 'Staff',
            'role' => $this->role->id,
            'address' => [
                'line1' => '456 New St',
                'city' => 'Boston',
                'state' => 'MA',
                'zip' => '02101',
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/staff/{$staffMember->id}", $updateData);

        $response->assertStatus(200);

        $staffMember->refresh();
        $this->assertNotNull($staffMember->address_id);

        $this->assertDatabaseHas('addresses', [
            'id' => $staffMember->address_id,
            'line1' => '456 New St',
            'city' => 'Boston',
        ]);
    }

    public function test_staff_can_change_staff_role(): void
    {
        $newRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Manager',
        ]);

        $staffMember = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'staff@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $updateData = [
            'email' => 'staff@account.com',
            'firstName' => 'Test',
            'lastName' => 'Staff',
            'role' => $newRole->id,
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/staff/{$staffMember->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('staff', [
            'id' => $staffMember->id,
            'account_role_id' => $newRole->id,
        ]);
    }

    public function test_cannot_update_staff_from_another_account(): void
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

        $otherStaff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'account_role_id' => $otherRole->id,
            'email' => 'other@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Other',
            'last_name' => 'Staff',
        ]);

        $updateData = [
            'email' => 'hacked@account.com',
            'firstName' => 'Hacked',
            'lastName' => 'Name',
            'role' => $otherRole->id,
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/staff/{$otherStaff->id}", $updateData);

        $response->assertStatus(404);
    }

    public function test_update_staff_validates_email_uniqueness(): void
    {
        $staffMember = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'unique@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $updateData = [
            'email' => $this->staff->email, // Already taken by another staff
            'firstName' => 'Test',
            'lastName' => 'Staff',
            'role' => $this->role->id,
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/staff/{$staffMember->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_staff_allows_keeping_same_email(): void
    {
        $staffMember = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'same@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $updateData = [
            'email' => 'same@account.com', // Same email
            'firstName' => 'Updated',
            'lastName' => 'Staff',
            'role' => $this->role->id,
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/staff/{$staffMember->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('staff', [
            'id' => $staffMember->id,
            'email' => 'same@account.com',
            'first_name' => 'Updated',
        ]);
    }

    public function test_update_staff_validates_required_fields(): void
    {
        $staffMember = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'test@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/staff/{$staffMember->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'firstName', 'lastName', 'role']);
    }

    public function test_unauthenticated_user_cannot_update_staff(): void
    {
        $updateData = [
            'email' => 'test@account.com',
            'firstName' => 'Test',
            'lastName' => 'Staff',
            'role' => $this->role->id,
        ];

        $response = $this->putJson("/api/staff/staff/{$this->staff->id}", $updateData);

        $response->assertStatus(401);
    }

    // DELETE /api/staff/staff/{id} Tests

    public function test_staff_can_delete_staff(): void
    {
        $staffMember = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->role->id,
            'email' => 'todelete@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'To',
            'last_name' => 'Delete',
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/staff/staff/{$staffMember->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('staff', [
            'id' => $staffMember->id,
        ]);
    }

    public function test_cannot_delete_staff_from_another_account(): void
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

        $otherStaff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'account_role_id' => $otherRole->id,
            'email' => 'other@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Other',
            'last_name' => 'Staff',
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/staff/staff/{$otherStaff->id}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_staff(): void
    {
        $response = $this->deleteJson("/api/staff/staff/{$this->staff->id}");

        $response->assertStatus(401);
    }
}
