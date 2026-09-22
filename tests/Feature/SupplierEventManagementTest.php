<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventChecklistGroup;
use App\Models\Supplier;
use App\Models\SupplierRole;
use App\Models\SupplierStaff;
use App\Models\SupplierTemplateChecklistGroup;
use App\Models\SupplierTemplateChecklist;
use App\Notifications\ClientWelcomeNotification;
use Illuminate\Database\QueryException;
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
            'eventType' => EventTypeEnum::Birthday->value,
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
                'eventType',
                'eventDate',
                'celebrantOne',
                'address',
            ]);

        $this->assertDatabaseHas('events', [
            'name' => 'John\'s Birthday',
            'supplier_id' => $this->supplier->id,
            'event_type' => 'Birthday',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('celebrants', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'email' => 'client1@example.com',
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_can_create_event_with_two_celebrants(): void
    {
        $eventData = [
            'name' => 'John and Jane Wedding',
            'description' => 'A beautiful wedding',
            'status' => 'Pending',
            'eventType' => EventTypeEnum::Wedding->value,
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
            'eventType' => EventTypeEnum::Debut->value,
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
            'eventType' => EventTypeEnum::Baptism->value,
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
            'eventType' => EventTypeEnum::Wedding->value,
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
            'eventType' => EventTypeEnum::Wedding->value,
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
            'eventType' => EventTypeEnum::Wedding->value,
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
            'updated_by' => $this->staff->id,
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

    // Timestamp and Editor Tracking Tests

    public function test_event_creation_tracks_created_at_and_updated_at(): void
    {
        $eventData = [
            'name' => 'Test Event',
            'status' => 'Pending',
            'eventType' => EventTypeEnum::Wedding->value,
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

        $beforeCreate = now()->subSecond(); // Subtract a second to avoid precision issues

        $response = $this->withToken($this->token)
            ->postJson('/api/suppliers/events', $eventData);

        $response->assertStatus(201);

        $event = Event::where('name', 'Test Event')->first();
        $this->assertNotNull($event);
        $this->assertNotNull($event->created_at);
        $this->assertNotNull($event->updated_at);
        $this->assertTrue($event->created_at->greaterThanOrEqualTo($beforeCreate));
        $this->assertEquals($event->created_at->format('Y-m-d H:i:s'), $event->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_event_update_changes_updated_at(): void
    {
        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $originalUpdatedAt = $event->updated_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        $updateData = [
            'name' => 'Updated Event Name',
            'status' => $event->status->value,
            'eventDate' => $event->event_date->toIso8601String(),
            'celebrantOne' => [
                'firstName' => $event->celebrantOne->first_name,
                'lastName' => $event->celebrantOne->last_name,
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

        $response->assertStatus(200);

        $event->refresh();
        $this->assertTrue($event->updated_at->greaterThan($originalUpdatedAt));
    }

    public function test_soft_delete_sets_deleted_at_timestamp(): void
    {
        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
        ]);

        $beforeDelete = now()->subSecond(); // Subtract a second to avoid precision issues

        $event->delete();

        $event->refresh();
        $this->assertNotNull($event->deleted_at);
        $this->assertTrue($event->deleted_at->greaterThanOrEqualTo($beforeDelete));
    }

    // ID Isolation and Foreign Key Constraint Tests

    public function test_cannot_create_event_with_admin_id_as_created_by(): void
    {
        // Create an Admin user
        $admin = Admin::create([
            'id' => Str::uuid()->toString(),
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        $failed = false;

        try {
            // Attempt to create an event with admin ID in created_by field
            // This should fail because created_by has a foreign key to supplier_staff table
            Event::create([
                'id' => Str::uuid()->toString(),
                'supplier_id' => $this->supplier->id,
                'name' => 'Test Event',
                'status' => EventStatusEnum::Pending,
                'event_type' => EventTypeEnum::Wedding,
                'event_date' => now()->addDays(30),
                'celebrant_one_id' => Str::uuid()->toString(),
                'created_by' => $admin->id, // This should fail - Admin ID in supplier_staff foreign key
                'updated_by' => $this->staff->id,
            ]);
        } catch (QueryException $e) {
            // Foreign key constraint should prevent this
            $this->assertStringContainsString('foreign key constraint', $e->getMessage());
            $failed = true;
        }

        $this->assertTrue($failed, 'Expected foreign key constraint violation when using admin ID in created_by field');
    }

    public function test_cannot_create_event_with_client_id_as_created_by(): void
    {
        // Create a Client user
        $client = Client::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'email' => 'client@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Client',
            'last_name' => 'User',
        ]);

        $failed = false;

        try {
            // Attempt to create an event with client ID in created_by field
            // This should fail because created_by has a foreign key to supplier_staff table
            Event::create([
                'id' => Str::uuid()->toString(),
                'supplier_id' => $this->supplier->id,
                'name' => 'Test Event',
                'status' => EventStatusEnum::Pending,
                'event_type' => EventTypeEnum::Birthday,
                'event_date' => now()->addDays(30),
                'celebrant_one_id' => Str::uuid()->toString(),
                'created_by' => $client->id, // This should fail - Client ID in supplier_staff foreign key
                'updated_by' => $this->staff->id,
            ]);
        } catch (QueryException $e) {
            // Foreign key constraint should prevent this
            $this->assertStringContainsString('foreign key constraint', $e->getMessage());
            $failed = true;
        }

        $this->assertTrue($failed, 'Expected foreign key constraint violation when using client ID in created_by field');
    }

    public function test_event_created_by_must_reference_valid_supplier_staff(): void
    {
        // This test verifies that only valid supplier_staff IDs can be used
        $validStaffId = $this->staff->id;

        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'created_by' => $validStaffId,
            'updated_by' => $validStaffId,
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'created_by' => $validStaffId,
            'updated_by' => $validStaffId,
        ]);
    }

    // Checklist Template Copying Tests

    public function test_creating_event_copies_template_checklists(): void
    {
        // Create template checklist group for Birthday events
        $templateGroup = SupplierTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Birthday Checklist',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        // Create template checklists with different frequencies
        $template1 = SupplierTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Book venue',
            'description' => 'Book the party venue',
            'frequency_days' => -30, // 30 days before event
        ]);

        $template2 = SupplierTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Send thank you cards',
            'description' => 'Send thank you notes',
            'frequency_days' => 7, // 7 days after event
        ]);

        // Create event
        $eventData = [
            'name' => 'Birthday Party',
            'status' => 'Pending',
            'eventType' => EventTypeEnum::Birthday->value,
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

        $response->assertStatus(201);

        $event = Event::where('name', 'Birthday Party')->first();

        // Assert event checklist group was created
        $this->assertDatabaseHas('event_checklist_groups', [
            'event_id' => $event->id,
            'name' => 'Birthday Checklist',
            'event_type' => 'Birthday',
        ]);

        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();

        // Assert event checklists were copied
        $this->assertDatabaseHas('event_checklists', [
            'event_checklist_group_id' => $eventGroup->id,
            'name' => 'Book venue',
            'description' => 'Book the party venue',
            'due_date' => '2024-11-25', // 30 days before Dec 25
        ]);

        $this->assertDatabaseHas('event_checklists', [
            'event_checklist_group_id' => $eventGroup->id,
            'name' => 'Send thank you cards',
            'description' => 'Send thank you notes',
            'due_date' => '2025-01-01', // 7 days after Dec 25
        ]);
    }

    public function test_only_copies_checklists_matching_event_type(): void
    {
        // Create template for Birthday
        $birthdayGroup = SupplierTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Birthday Tasks',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        SupplierTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'supplier_template_checklist_group_id' => $birthdayGroup->id,
            'name' => 'Birthday Task',
            'frequency_days' => -7,
        ]);

        // Create template for Wedding
        $weddingGroup = SupplierTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Wedding Tasks',
            'event_type' => EventTypeEnum::Wedding,
        ]);

        SupplierTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'supplier_template_checklist_group_id' => $weddingGroup->id,
            'name' => 'Wedding Task',
            'frequency_days' => -14,
        ]);

        // Create Wedding event
        $eventData = [
            'name' => 'Wedding Event',
            'status' => 'Pending',
            'eventType' => EventTypeEnum::Wedding->value,
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

        $response->assertStatus(201);

        $event = Event::where('name', 'Wedding Event')->first();

        // Assert only Wedding checklist group was created
        $this->assertDatabaseHas('event_checklist_groups', [
            'event_id' => $event->id,
            'name' => 'Wedding Tasks',
            'event_type' => 'Wedding',
        ]);

        // Assert Birthday checklist group was NOT created
        $this->assertDatabaseMissing('event_checklist_groups', [
            'event_id' => $event->id,
            'name' => 'Birthday Tasks',
        ]);

        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();

        // Assert Wedding task was copied
        $this->assertDatabaseHas('event_checklists', [
            'event_checklist_group_id' => $eventGroup->id,
            'name' => 'Wedding Task',
        ]);

        // Assert Birthday task was NOT copied
        $this->assertDatabaseMissing('event_checklists', [
            'name' => 'Birthday Task',
        ]);
    }
}

