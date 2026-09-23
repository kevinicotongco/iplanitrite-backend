<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Country $country;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Mail::fake();

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

        $this->admin = Admin::create([
            'id' => Str::uuid()->toString(),
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Admin',
        ]);

        $this->token = $this->admin->createToken('admin-token')->plainTextToken;
    }

    public function test_admin_can_get_accounts_list(): void
    {
        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Account One',
            'status' => AccountStatusEnum::Active,
            'description' => 'Test account',
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'status',
                    'logo',
                    'description',
                    'address',
                    'country',
                    'contactNumber',
                    'subscriptionTier',
                    'timezone',
                ],
            ]);
    }

    public function test_accounts_are_ordered_by_name_ascending(): void
    {
        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Zebra Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Alpha Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/accounts');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('Alpha Account', $data[0]['name']);
        $this->assertEquals('Zebra Account', $data[1]['name']);
    }

    public function test_accounts_can_be_filtered_by_search_text(): void
    {
        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Acme Events',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Best Catering',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/accounts?searchText=Acme');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('Acme Events', $data[0]['name']);
    }

    public function test_accounts_can_be_filtered_by_status(): void
    {
        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Active Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Disabled Account',
            'status' => AccountStatusEnum::Disabled,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/accounts?status=Active');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('Active', $data[0]['status']);
    }

    public function test_accounts_can_be_filtered_by_tier(): void
    {
        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Free Tier Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Premium Tier Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Premium,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/accounts?tier=Premium');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('Premium', $data[0]['subscriptionTier']);
    }

    public function test_accounts_can_be_filtered_by_country(): void
    {
        $otherCountry = Country::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Canada',
            'iso2_code' => 'CA',
            'iso3_code' => 'CAN',
            'language_locale' => 'en-CA',
            'calling_code' => '+1',
            'flag' => '🇨🇦',
            'currency_code' => 'CAD',
            'currency_name' => 'Canadian Dollar',
            'currency_symbol' => '$',
        ]);

        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'US Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Canada Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $otherCountry->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/Toronto',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/accounts?countryId=' . $this->country->id);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('US Account', $data[0]['name']);
    }

    public function test_get_accounts_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/accounts');

        $response->assertStatus(401);
    }

    // POST /api/admin/accounts Tests

    public function test_admin_can_create_account_with_minimal_data(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/admin/accounts', [
                'name' => 'New Account',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'staff' => [
                    'email' => 'staff@newaccount.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'status',
                'subscriptionTier',
                'timezone',
            ]);

        $this->assertDatabaseHas('accounts', [
            'name' => 'New Account',
            'status' => AccountStatusEnum::Active->value,
        ]);

        $this->assertDatabaseHas('staff', [
            'email' => 'staff@newaccount.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('account_roles', [
            'name' => 'Administrator',
        ]);
    }

    public function test_admin_can_create_account_with_full_data(): void
    {
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->withToken($this->token)
            ->postJson('/api/admin/accounts', [
                'name' => 'Full Data Account',
                'logo' => $logo,
                'description' => 'A comprehensive account',
                'subscriptionTier' => 'Premium',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'address' => [
                    'line1' => '123 Main St',
                    'line2' => 'Suite 100',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                    'lat' => '40.7128',
                    'long' => '-74.0060',
                ],
                'contactNumber' => '+1234567890',
                'staff' => [
                    'email' => 'admin@fullaccount.com',
                    'firstName' => 'Jane',
                    'middleName' => 'M',
                    'lastName' => 'Smith',
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('accounts', [
            'name' => 'Full Data Account',
            'description' => 'A comprehensive account',
        ]);

        $this->assertDatabaseHas('addresses', [
            'line1' => '123 Main St',
            'city' => 'New York',
        ]);

        $this->assertDatabaseHas('contact_numbers', [
            'number' => '+1234567890',
        ]);

        $this->assertDatabaseHas('staff', [
            'email' => 'admin@fullaccount.com',
            'middle_name' => 'M',
        ]);
    }

    public function test_create_account_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/admin/accounts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'subscriptionTier',
                'countryId',
                'timezone',
                'staff',
            ]);
    }

    public function test_create_account_validates_email_uniqueness(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/admin/accounts', [
                'name' => 'First Account',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'staff' => [
                    'email' => 'duplicate@test.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/admin/accounts', [
                'name' => 'Second Account',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'staff' => [
                    'email' => 'duplicate@test.com',
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['staff.email']);
    }

    public function test_create_account_validates_subscription_tier(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/admin/accounts', [
                'name' => 'Test Account',
                'subscriptionTier' => 'InvalidTier',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'staff' => [
                    'email' => 'test@test.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subscriptionTier']);
    }

    public function test_create_account_requires_authentication(): void
    {
        $response = $this->postJson('/api/admin/accounts', [
            'name' => 'Test Account',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_update_account(): void
    {
        $account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Original Name',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/accounts/' . $account->id, [
                'name' => 'Updated Name',
                'subscriptionTier' => 'Premium',
                'countryId' => $this->country->id,
                'timezone' => 'America/Los_Angeles',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Name',
            'subscription_tier' => 'Premium',
            'timezone' => 'America/Los_Angeles',
        ]);
    }

    public function test_admin_can_update_account_address(): void
    {
        $account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/accounts/' . $account->id, [
                'name' => 'Test Account',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'address' => [
                    'line1' => '456 Oak Ave',
                    'city' => 'Boston',
                    'state' => 'MA',
                    'zip' => '02101',
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('addresses', [
            'line1' => '456 Oak Ave',
            'city' => 'Boston',
        ]);
    }

    public function test_update_account_validates_required_fields(): void
    {
        $account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/accounts/' . $account->id, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'subscriptionTier',
                'countryId',
                'timezone',
            ]);
    }

    public function test_update_account_returns_404_for_nonexistent_account(): void
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/accounts/' . $fakeId, [
                'name' => 'Test',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_account_requires_authentication(): void
    {
        $account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->putJson('/api/admin/accounts/' . $account->id, [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }
}
