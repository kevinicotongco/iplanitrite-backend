<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    private Account $ac;
    private Client $client;
    private Admin $admin;
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

        $this->client = Client::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'email' => 'client@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Client',
        ]);

        $this->admin = Admin::create([
            'id' => Str::uuid()->toString(),
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Admin',
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

    public function test_client_can_view_profile(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->getJson('/api/clients/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'accountId',
                'email',
                'firstName',
                'middleName',
                'lastName',
                'profilePicture',
                'address',
                'contactNumber',
            ])
            ->assertJson([
                'id' => $this->client->id,
                'email' => 'client@test.com',
                'firstName' => 'Test',
                'lastName' => 'Client',
            ]);
    }

    public function test_unauthenticated_user_cannot_view_client_profile(): void
    {
        $response = $this->getJson('/api/clients/profile');

        $response->assertStatus(401);
    }

    public function test_admin_cannot_view_client_profile(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/clients/profile');

        $response->assertStatus(401);
    }

    public function test_staff_cannot_view_client_profile(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->getJson('/api/clients/profile');

        $response->assertStatus(401);
    }
}
