<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientChangePasswordTest extends TestCase
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

    public function test_client_can_change_password(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(204);

        $this->client->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->client->password));
    }

    public function test_client_change_password_validates_current_password(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/password', [
                'currentPassword' => 'wrongpassword',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currentPassword']);
    }

    public function test_client_change_password_validates_new_password_confirmation(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'differentpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }

    public function test_client_change_password_validates_minimum_length(): void
    {
        $response = $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'short',
                'newPasswordConfirmation' => 'short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }
}
