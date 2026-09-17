<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientUpdateProfileTest extends TestCase
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

    public function test_client_can_update_profile(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/profile', [
                'firstName' => 'Updated',
                'middleName' => 'Middle',
                'lastName' => 'Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'supplierId',
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

        $this->assertDatabaseHas('clients', [
            'id' => $this->client->id,
            'first_name' => 'Updated',
            'middle_name' => 'Middle',
            'last_name' => 'Name',
        ]);
    }

    public function test_client_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['firstName', 'lastName']);
    }

    public function test_unauthenticated_user_cannot_update_client_profile(): void
    {
        $response = $this->putJson('/api/clients/profile', [
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]);

        $response->assertStatus(401);
    }
}
