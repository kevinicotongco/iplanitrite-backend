<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSupplierManagementTest extends TestCase
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

    // GET /api/admin/suppliers Tests

    public function test_admin_can_get_suppliers_list(): void
    {
        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Supplier One',
            'status' => SupplierStatusEnum::Active,
            'description' => 'Test supplier',
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/suppliers');

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

    public function test_suppliers_are_ordered_by_name_ascending(): void
    {
        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Zebra Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Alpha Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/suppliers');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('Alpha Supplier', $data[0]['name']);
        $this->assertEquals('Zebra Supplier', $data[1]['name']);
    }

    public function test_suppliers_can_be_filtered_by_search_text(): void
    {
        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Acme Events',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Best Catering',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/suppliers?searchText=Acme');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('Acme Events', $data[0]['name']);
    }

    public function test_suppliers_can_be_filtered_by_status(): void
    {
        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Active Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Disabled Supplier',
            'status' => SupplierStatusEnum::Disabled,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/suppliers?status=Active');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('Active', $data[0]['status']);
    }

    public function test_suppliers_can_be_filtered_by_tier(): void
    {
        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Free Tier Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Premium Tier Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Premium,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/suppliers?tier=Premium');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('Premium', $data[0]['subscriptionTier']);
    }

    public function test_suppliers_can_be_filtered_by_country(): void
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

        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'US Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Canada Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $otherCountry->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/Toronto',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/admin/suppliers?countryId=' . $this->country->id);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(1, $data);
        $this->assertEquals('US Supplier', $data[0]['name']);
    }

    public function test_get_suppliers_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/suppliers');

        $response->assertStatus(401);
    }

    // POST /api/admin/suppliers Tests

    public function test_admin_can_create_supplier_with_minimal_data(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/admin/suppliers', [
                'name' => 'New Supplier',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'supplierStaff' => [
                    'email' => 'staff@newsupplier.com',
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

        $this->assertDatabaseHas('suppliers', [
            'name' => 'New Supplier',
            'status' => SupplierStatusEnum::Active->value,
        ]);

        $this->assertDatabaseHas('supplier_staff', [
            'email' => 'staff@newsupplier.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('supplier_roles', [
            'name' => 'Administrator',
        ]);
    }

    public function test_admin_can_create_supplier_with_full_data(): void
    {
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->withToken($this->token)
            ->postJson('/api/admin/suppliers', [
                'name' => 'Full Data Supplier',
                'logo' => $logo,
                'description' => 'A comprehensive supplier',
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
                'supplierStaff' => [
                    'email' => 'admin@fullsupplier.com',
                    'firstName' => 'Jane',
                    'middleName' => 'M',
                    'lastName' => 'Smith',
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Full Data Supplier',
            'description' => 'A comprehensive supplier',
        ]);

        $this->assertDatabaseHas('addresses', [
            'line1' => '123 Main St',
            'city' => 'New York',
        ]);

        $this->assertDatabaseHas('contact_numbers', [
            'number' => '+1234567890',
        ]);

        $this->assertDatabaseHas('supplier_staff', [
            'email' => 'admin@fullsupplier.com',
            'middle_name' => 'M',
        ]);
    }

    public function test_create_supplier_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/admin/suppliers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'subscriptionTier',
                'countryId',
                'timezone',
                'supplierStaff',
            ]);
    }

    public function test_create_supplier_validates_email_uniqueness(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/admin/suppliers', [
                'name' => 'First Supplier',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'supplierStaff' => [
                    'email' => 'duplicate@test.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/admin/suppliers', [
                'name' => 'Second Supplier',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'supplierStaff' => [
                    'email' => 'duplicate@test.com',
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['supplierStaff.email']);
    }

    public function test_create_supplier_validates_subscription_tier(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/admin/suppliers', [
                'name' => 'Test Supplier',
                'subscriptionTier' => 'InvalidTier',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
                'supplierStaff' => [
                    'email' => 'test@test.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subscriptionTier']);
    }

    public function test_create_supplier_requires_authentication(): void
    {
        $response = $this->postJson('/api/admin/suppliers', [
            'name' => 'Test Supplier',
        ]);

        $response->assertStatus(401);
    }

    // PUT /api/admin/suppliers/{id} Tests

    public function test_admin_can_update_supplier(): void
    {
        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Original Name',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/suppliers/' . $supplier->id, [
                'name' => 'Updated Name',
                'subscriptionTier' => 'Premium',
                'countryId' => $this->country->id,
                'timezone' => 'America/Los_Angeles',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Name',
            'subscription_tier' => 'Premium',
            'timezone' => 'America/Los_Angeles',
        ]);
    }

    public function test_admin_can_update_supplier_address(): void
    {
        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/suppliers/' . $supplier->id, [
                'name' => 'Test Supplier',
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

    public function test_update_supplier_validates_required_fields(): void
    {
        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/suppliers/' . $supplier->id, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'subscriptionTier',
                'countryId',
                'timezone',
            ]);
    }

    public function test_update_supplier_returns_404_for_nonexistent_supplier(): void
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withToken($this->token)
            ->putJson('/api/admin/suppliers/' . $fakeId, [
                'name' => 'Test',
                'subscriptionTier' => 'Free',
                'countryId' => $this->country->id,
                'timezone' => 'America/New_York',
            ]);

        $response->assertStatus(404);
    }

    public function test_update_supplier_requires_authentication(): void
    {
        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $response = $this->putJson('/api/admin/suppliers/' . $supplier->id, [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }
}
