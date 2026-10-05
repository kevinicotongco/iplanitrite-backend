<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesBase64Images;
use Tests\TestCase;

class StaffUpdateProfileTest extends TestCase
{
    use CreatesBase64Images, RefreshDatabase;

    private Account $acc;
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

    public function test_staff_can_update_profile(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/profile', [
                'firstName' => 'Updated',
                'middleName' => 'Middle',
                'lastName' => 'Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
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
            ])
            ->assertJson([
                'firstName' => 'Updated',
                'middleName' => 'Middle',
                'lastName' => 'Name',
            ]);

        $this->assertDatabaseHas('staff', [
            'id' => $this->staff->id,
            'first_name' => 'Updated',
            'middle_name' => 'Middle',
            'last_name' => 'Name',
        ]);
    }

    public function test_staff_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['firstName', 'lastName']);
    }

    public function test_unauthenticated_user_cannot_update_staff_profile(): void
    {
        $response = $this->putJson('/api/staff/profile', [
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_staff_can_update_avatar_with_base64_png(): void
    {
        $avatar = $this->base64Png();

        $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/profile', ['avatar' => $avatar, 'firstName' => 'Updated', 'lastName' => 'Name'])
            ->assertStatus(200)
            ->assertJsonPath('profilePicture', $avatar);

        $this->assertDatabaseHas('staff', ['id' => $this->staff->id, 'profile_picture' => $avatar]);
    }

    public function test_staff_can_update_avatar_with_base64_jpeg_data_uri(): void
    {
        $avatar = 'data:image/jpeg;base64,' . $this->base64Jpeg();

        $this->actingAs($this->staff, 'staff')
            ->putJson('/api/staff/profile', ['avatar' => $avatar, 'firstName' => 'Updated', 'lastName' => 'Name'])
            ->assertStatus(200)
            ->assertJsonPath('profilePicture', $avatar);
    }

    public function test_staff_update_rejects_invalid_avatars(): void
    {
        foreach ($this->invalidAvatarPayloads() as [$payload]) {
            $this->actingAs($this->staff, 'staff')
                ->putJson('/api/staff/profile', ['avatar' => $payload, 'firstName' => 'Updated', 'lastName' => 'Name'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['avatar']);
        }

        $this->assertDatabaseHas('staff', ['id' => $this->staff->id, 'profile_picture' => null]);
    }
}
