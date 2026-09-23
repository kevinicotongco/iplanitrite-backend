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

class StaffChangePasswordTest extends TestCase
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

    public function test_staff_can_change_password(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(204);

        $this->staff->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->staff->password));
    }

    public function test_staff_change_password_validates_current_password(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/password', [
                'currentPassword' => 'wrongpassword',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currentPassword']);
    }

    public function test_staff_change_password_validates_new_password_confirmation(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'differentpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }

    public function test_staff_change_password_validates_minimum_length(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'short',
                'newPasswordConfirmation' => 'short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }
}
