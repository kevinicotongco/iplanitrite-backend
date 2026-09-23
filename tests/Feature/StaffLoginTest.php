<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffLoginTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private AccountRole $accountRole;
    private Staff $staff;

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

        $this->accountRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Manager',
        ]);

        $this->staff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $this->accountRole->id,
            'email' => 'staff@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);
    }

    public function test_staff_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/staff/login', [
            'email' => 'staff@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
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
            ]);
    }

    public function test_staff_cannot_login_with_invalid_email(): void
    {
        $response = $this->postJson('/api/staff/login', [
            'email' => 'wrong@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_staff_cannot_login_with_invalid_password(): void
    {
        $response = $this->postJson('/api/staff/login', [
            'email' => 'staff@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_validates_email_format(): void
    {
        $response = $this->postJson('/api/staff/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/staff/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
