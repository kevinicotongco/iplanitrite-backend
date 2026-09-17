<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientLoginTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;
    private Client $client;

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

        $this->client = Client::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'email' => 'client@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Client',
        ]);
    }

    public function test_client_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/clients/login', [
            'email' => 'client@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'supplierId',
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

    public function test_client_cannot_login_with_invalid_email(): void
    {
        $response = $this->postJson('/api/clients/login', [
            'email' => 'wrong@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_client_cannot_login_with_invalid_password(): void
    {
        $response = $this->postJson('/api/clients/login', [
            'email' => 'client@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_validates_email_format(): void
    {
        $response = $this->postJson('/api/clients/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/clients/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
