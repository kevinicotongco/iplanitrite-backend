<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatusEnum;
use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use App\Models\Client;
use App\Models\Country;
use App\Models\Event;
use App\Models\Supplier;
use App\Models\SupplierRole;
use App\Models\SupplierStaff;
use App\Notifications\ClientWelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierEventManagementTest extends TestCase
{
    use RefreshDatabase;

    private SupplierStaff $staff;
    private Supplier $supplier;
    private Country $country;
    private SupplierRole $role;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

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

        $this->supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
        ]);

        $this->role = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Admin',
        ]);

        $this->staff = SupplierStaff::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'supplier_role_id' => $this->role->id,
            'email' => 'staff@supplier.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $this->token = $this->staff->createToken('staff-token')->plainTextToken;
    }

    // GET /api/suppliers/events Tests

    public function test_supplier_staff_can_get_events_list(): void
    {
        Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Wedding Event',
            'status' => EventStatusEnum::Pending,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/suppliers/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'supplierId',
                    'name',
                    'description',
                    'status',
                    'eventDate',
                    'celebrantOne',
                    'celebrantTwo',
                    'address',
                ],
            ]);
    }

    public function test_can_filter_events_by_search_text(): void
    {
        Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Wedding Event',
            'status' => EventStatusEnum::Pending,
        ]);

        Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Birthday Party',
            'status' => EventStatusEnum::Pending,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/suppliers/events?searchText=Wedding');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_filter_events_by_status(): void
    {
        Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Pending Event',
            'status' => EventStatusEnum::Pending,
        ]);

        Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Completed Event',
            'status' => EventStatusEnum::Completed,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/suppliers/events?status=Completed');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_unauthenticated_user_cannot_get_events(): void
    {
        $response = $this->getJson('/api/suppliers/events');

        $response->assertStatus(401);
    }

    // POST /api/suppliers/events Tests

    public function test_can_create_event_with_one_celebrant(): void
    {
        $eventData = [
            'name' => 'John\'s Birthday',
            'description' => 'A fun birthday party',
            'status' => 'Pending',
            'eventDate' => '2024-12-25T14:00:00Z',
            'celebrantOne' => [
                'firstName' => 'John',
                'middleName' => 'Michael',
                'lastName' => 'Doe',
                'contactNumber' => '+12345678901',
            ],
            'address' => [
                'line1' => '123 Main St',
                'line2' => 'Apt 4B',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'lat' => '40.712776',
                'long' => '-74.005974',
            ],
            'clients' => [
                [
                    'email' => 'client1@example.com',
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'supplierId',
                'name',
                'description',
                'status',
                'eventDate',
                'celebrantOne',
                'address',
            ]);

        $this->assertDatabaseHas('events', [
            'name' => 'John\'s Birthday',
            'supplier_id' => $this->supplier->id,
        ]);

        $this->assertDatabaseHas('celebrants', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('clients', [
            'email' => 'client1@example.com',
            'supplier_id' => $this->supplier->id,
        ]);
    }

    public function test_can_create_event_with_two_celebrants(): void
    {
        $eventData = [
            'name' => 'John and Jane Wedding',
            'description' => 'A beautiful wedding',
            'status' => 'Pending',
            'eventDate' => '2024-12-25T14:00:00Z',
            'celebrantOne' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'celebrantTwo' => [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
            ],
            'address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
            ],
            'clients' => [
                [
                    'email' => 'client@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'User',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(201)
            ->assertJsonPath('celebrantTwo.firstName', 'Jane');

        $this->assertDatabaseHas('celebrants', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_creates_client_and_sends_notification(): void
    {
        $eventData = [
            'name' => 'Test Event',
            'status' => 'Pending',
            'eventDate' => '2024-12-25T14:00:00Z',
            'celebrantOne' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
            ],
            'clients' => [
                [
                    'email' => 'newclient@example.com',
                    'firstName' => 'New',
                    'lastName' => 'Client',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(201);

        $client = Client::where('email', 'newclient@example.com')->first();
        $this->assertNotNull($client);

        Notification::assertSentTo(
            [$client],
            ClientWelcomeNotification::class
        );
    }

    public function test_can_attach_existing_client_to_event(): void
    {
        $existingClient = Client::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
            'first_name' => 'Existing',
            'last_name' => 'Client',
        ]);

        $eventData = [
            'name' => 'Test Event',
            'status' => 'Pending',
            'eventDate' => '2024-12-25T14:00:00Z',
            'celebrantOne' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
            ],
            'clients' => [
                [
                    'email' => 'existing@example.com',
                    'firstName' => 'Existing',
                    'lastName' => 'Client',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(201);

        // Should not create duplicate client
        $this->assertEquals(1, Client::where('email', 'existing@example.com')->count());
    }

    public function test_validation_fails_when_name_is_missing(): void
    {
        $eventData = [
            'status' => 'Pending',
            'eventDate' => '2024-12-25T14:00:00Z',
            'celebrantOne' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
            ],
            'clients' => [
                [
                    'email' => 'client@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'User',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_fails_when_clients_array_is_empty(): void
    {
        $eventData = [
            'name' => 'Test Event',
            'status' => 'Pending',
            'eventDate' => '2024-12-25T14:00:00Z',
            'celebrantOne' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
            ],
            'clients' => [],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['clients']);
    }

    public function test_unauthenticated_user_cannot_create_event(): void
    {
        $eventData = [
            'name' => 'Test Event',
            'status' => 'Pending',
            'eventDate' => '2024-12-25T14:00:00Z',
            'celebrantOne' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
            ],
            'clients' => [
                [
                    'email' => 'client@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'User',
                ],
            ],
        ];

        $response = $this->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(401);
    }

    // PUT /api/suppliers/events/{id} Tests

    public function test_can_update_event(): void
    {
        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Original Name',
            'status' => EventStatusEnum::Pending,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'status' => 'Ongoing',
            'eventDate' => '2024-12-31T18:00:00Z',
            'celebrantOne' => [
                'firstName' => 'Updated',
                'lastName' => 'Name',
            ],
            'address' => [
                'line1' => '456 New St',
                'city' => 'Boston',
                'state' => 'MA',
                'zip' => '02101',
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/suppliers/events/{$event->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Name')
            ->assertJsonPath('status', 'Ongoing');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Name',
            'status' => EventStatusEnum::Ongoing,
        ]);
    }

    public function test_can_add_second_celebrant_to_event(): void
    {
        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'celebrant_two_id' => null,
        ]);

        $updateData = [
            'name' => $event->name,
            'status' => $event->status->value,
            'eventDate' => $event->event_date->toIso8601String(),
            'celebrantOne' => [
                'firstName' => $event->celebrantOne->first_name,
                'lastName' => $event->celebrantOne->last_name,
            ],
            'celebrantTwo' => [
                'firstName' => 'Second',
                'lastName' => 'Celebrant',
            ],
            'address' => [
                'line1' => $event->address->line1,
                'city' => $event->address->city,
                'state' => $event->address->state,
                'zip' => $event->address->zip,
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/suppliers/events/{$event->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('celebrantTwo.firstName', 'Second');

        $event->refresh();
        $this->assertNotNull($event->celebrant_two_id);
    }

    public function test_cannot_update_event_from_another_supplier(): void
    {
        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Supplier',
            'status' => SupplierStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
        ]);

        $event = Event::factory()->create([
            'supplier_id' => $otherSupplier->id,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'status' => 'Ongoing',
            'eventDate' => '2024-12-31T18:00:00Z',
            'celebrantOne' => [
                'firstName' => 'Updated',
                'lastName' => 'Name',
            ],
            'address' => [
                'line1' => '456 New St',
                'city' => 'Boston',
                'state' => 'MA',
                'zip' => '02101',
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/suppliers/events/{$event->id}", $updateData);

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_update_event(): void
    {
        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'status' => 'Ongoing',
            'eventDate' => '2024-12-31T18:00:00Z',
            'celebrantOne' => [
                'firstName' => 'Updated',
                'lastName' => 'Name',
            ],
            'address' => [
                'line1' => '456 New St',
                'city' => 'Boston',
                'state' => 'MA',
                'zip' => '02101',
            ],
        ];

        $response = $this->putJson("/api/suppliers/events/{$event->id}", $updateData);

        $response->assertStatus(401);
    }
}
