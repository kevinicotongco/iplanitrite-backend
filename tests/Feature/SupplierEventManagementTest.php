<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\EventSegment;
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
        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Wedding Event',
            'status' => EventStatusEnum::Pending,
        ]);

        EventSegment::factory()->create([
            'event_id' => $event->id,
            'name' => 'Wedding',
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
                    'eventType',
                    'celebrantOne',
                    'celebrantTwo',
                    'primarySegments' => [
                        '*' => [
                            'id',
                            'name',
                            'isPrimary',
                            'date',
                            'startTime',
                            'endTime',
                            'address',
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_filter_events_by_search_text(): void
    {
        $event1 = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Wedding Event',
            'status' => EventStatusEnum::Pending,
        ]);
        EventSegment::factory()->create(['event_id' => $event1->id]);

        $event2 = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Birthday Party',
            'status' => EventStatusEnum::Pending,
        ]);
        EventSegment::factory()->create(['event_id' => $event2->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/suppliers/events?searchText=Wedding');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_filter_events_by_status(): void
    {
        $event1 = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Pending Event',
            'status' => EventStatusEnum::Pending,
        ]);
        EventSegment::factory()->create(['event_id' => $event1->id]);

        $event2 = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Completed Event',
            'status' => EventStatusEnum::Completed,
        ]);
        EventSegment::factory()->create(['event_id' => $event2->id]);

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
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'middleName' => 'Michael',
                'lastName' => 'Doe',
                'contactNumber' => '+12345678901',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'line2' => 'Apt 4B',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                    'lat' => '40.712776',
                    'long' => '-74.005974',
                ],
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

        $response->assertStatus(201);

        $this->assertDatabaseHas('events', [
            'name' => 'John\'s Birthday',
            'supplier_id' => $this->supplier->id,
            'event_type' => 'Birthday',
            'status' => 'Pending',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('celebrants', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('event_segments', [
            'name' => 'Birthday',
            'date' => '2024-12-25',
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
            'eventType' => EventTypeEnum::Wedding->value,
            'celebrants' => [
                'bride' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
                'groom' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ],
            'segments' => [
                'wedding' => [
                    'date' => '2024-12-25',
                    'startTime' => '14:00:00',
                    'endTime' => '16:00:00',
                    'address' => [
                        'line1' => '123 Main St',
                        'city' => 'New York',
                        'state' => 'NY',
                        'zip' => '10001',
                    ],
                ],
                'reception' => [
                    'date' => '2024-12-25',
                    'startTime' => '18:00:00',
                    'endTime' => '23:00:00',
                    'address' => [
                        'line1' => '456 Party Ave',
                        'city' => 'New York',
                        'state' => 'NY',
                        'zip' => '10002',
                    ],
                ],
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

        $this->assertDatabaseHas('celebrants', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertDatabaseHas('celebrants', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('event_segments', [
            'name' => 'Wedding',
        ]);

        $this->assertDatabaseHas('event_segments', [
            'name' => 'Reception',
        ]);
    }

    public function test_creates_client_and_sends_notification(): void
    {
        $eventData = [
            'name' => 'Test Event',
            'eventType' => EventTypeEnum::Debut->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
            'eventType' => EventTypeEnum::Baptism->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '16:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
            'eventType' => EventTypeEnum::Wedding->value,
            'celebrants' => [
                'bride' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Doe',
                ],
                'groom' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ],
            'segments' => [
                'wedding' => [
                    'date' => '2024-12-25',
                    'startTime' => '14:00:00',
                    'endTime' => '16:00:00',
                    'address' => [
                        'line1' => '123 Main St',
                        'city' => 'New York',
                        'state' => 'NY',
                        'zip' => '10001',
                    ],
                ],
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
            'eventType' => EventTypeEnum::Wedding->value,
            'celebrants' => [
                'bride' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Doe',
                ],
                'groom' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ],
            'segments' => [
                'wedding' => [
                    'date' => '2024-12-25',
                    'startTime' => '14:00:00',
                    'endTime' => '16:00:00',
                    'address' => [
                        'line1' => '123 Main St',
                        'city' => 'New York',
                        'state' => 'NY',
                        'zip' => '10001',
                    ],
                ],
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
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
            'event_type' => EventTypeEnum::Birthday,
        ]);
        EventSegment::factory()->create(['event_id' => $event->id]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'status' => 'Ongoing',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'Updated',
                'lastName' => 'Name',
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/suppliers/events/{$event->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Name',
            'status' => EventStatusEnum::Ongoing->value,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_can_add_second_celebrant_to_event(): void
    {
        $event = Event::factory()->create([
            'supplier_id' => $this->supplier->id,
            'celebrant_two_id' => null,
            'event_type' => EventTypeEnum::Wedding,
        ]);
        EventSegment::factory()->create(['event_id' => $event->id]);

        $updateData = [
            'name' => $event->name,
            'status' => $event->status->value,
            'eventType' => EventTypeEnum::Wedding->value,
            'celebrants' => [
                'bride' => [
                    'firstName' => $event->celebrantOne->first_name,
                    'lastName' => $event->celebrantOne->last_name,
                ],
                'groom' => [
                    'firstName' => 'Second',
                    'lastName' => 'Celebrant',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->putJson("/api/suppliers/events/{$event->id}", $updateData);

        $response->assertStatus(200);

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
            'event_type' => EventTypeEnum::Birthday,
        ]);
        EventSegment::factory()->create(['event_id' => $event->id]);

        $updateData = [
            'name' => 'Updated Name',
            'status' => 'Ongoing',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'Updated',
                'lastName' => 'Name',
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
            'event_type' => EventTypeEnum::Birthday,
        ]);
        EventSegment::factory()->create(['event_id' => $event->id]);

        $updateData = [
            'name' => 'Updated Name',
            'status' => 'Ongoing',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'Updated',
                'lastName' => 'Name',
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
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
            'clients' => [
                [
                    'email' => 'client@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'User',
                ],
            ],
        ];

        $beforeCreate = now()->subSecond();

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
            'event_type' => EventTypeEnum::Birthday,
        ]);
        EventSegment::factory()->create(['event_id' => $event->id]);

        $originalUpdatedAt = $event->updated_at;

        sleep(1);

        $updateData = [
            'name' => 'Updated Event Name',
            'status' => $event->status->value,
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => $event->celebrantOne->first_name,
                'lastName' => $event->celebrantOne->last_name,
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

        $beforeDelete = now()->subSecond();

        $event->delete();

        $event->refresh();
        $this->assertNotNull($event->deleted_at);
        $this->assertTrue($event->deleted_at->greaterThanOrEqualTo($beforeDelete));
    }

    // ID Isolation and Foreign Key Constraint Tests

    public function test_cannot_create_event_with_admin_id_as_created_by(): void
    {
        $admin = Admin::create([
            'id' => Str::uuid()->toString(),
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        $failed = false;

        try {
            Event::create([
                'id' => Str::uuid()->toString(),
                'supplier_id' => $this->supplier->id,
                'name' => 'Test Event',
                'status' => EventStatusEnum::Pending,
                'event_type' => EventTypeEnum::Wedding,
                'celebrant_one_id' => Str::uuid()->toString(),
                'created_by' => $admin->id,
                'updated_by' => $this->staff->id,
            ]);
        } catch (QueryException $e) {
            $this->assertStringContainsString('foreign key constraint', $e->getMessage());
            $failed = true;
        }

        $this->assertTrue($failed, 'Expected foreign key constraint violation when using admin ID in created_by field');
    }

    public function test_cannot_create_event_with_client_id_as_created_by(): void
    {
        $client = Client::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'email' => 'client@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Client',
            'last_name' => 'User',
        ]);

        $failed = false;

        try{
            Event::create([
                'id' => Str::uuid()->toString(),
                'supplier_id' => $this->supplier->id,
                'name' => 'Test Event',
                'status' => EventStatusEnum::Pending,
                'event_type' => EventTypeEnum::Birthday,
                'celebrant_one_id' => Str::uuid()->toString(),
                'created_by' => $client->id,
                'updated_by' => $this->staff->id,
            ]);
        } catch (QueryException $e) {
            $this->assertStringContainsString('foreign key constraint', $e->getMessage());
            $failed = true;
        }

        $this->assertTrue($failed, 'Expected foreign key constraint violation when using client ID in created_by field');
    }

    public function test_event_created_by_must_reference_valid_supplier_staff(): void
    {
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
        $templateGroup = SupplierTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $this->supplier->id,
            'name' => 'Birthday Checklist',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        $template1 = SupplierTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Book venue',
            'description' => 'Book the party venue',
            'frequency_days' => 30,
            'frequency_type' => ChecklistFrequencyTypeEnum::Days->value,
            'frequency_anchor' => FrequencyAnchorEnum::BeforeEvent->value,
        ]);

        $template2 = SupplierTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Send thank you cards',
            'description' => 'Send thank you notes',
            'frequency_days' => 7,
            'frequency_type' => ChecklistFrequencyTypeEnum::Days->value,
            'frequency_anchor' => FrequencyAnchorEnum::AfterCreation->value,
        ]);

        $createdAt = now();
        $eventData = [
            'name' => 'Birthday Party',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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

        $this->assertDatabaseHas('event_checklist_groups', [
            'event_id' => $event->id,
            'name' => 'Birthday Checklist',
            'event_type' => 'Birthday',
        ]);

        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();

        $this->assertDatabaseHas('event_checklists', [
            'event_checklist_group_id' => $eventGroup->id,
            'name' => 'Book venue',
            'description' => 'Book the party venue',
            'due_date' => '2024-11-25',
        ]);

        $checklist = EventChecklist::where('event_checklist_group_id', $eventGroup->id)
            ->where('name', 'Send thank you cards')
            ->first();

        $this->assertNotNull($checklist);
        $this->assertEquals('Send thank you notes', $checklist->description);
        $expectedDueDate = now()->addDays(7)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $checklist->due_date->format('Y-m-d'));
    }

    public function test_only_copies_checklists_matching_event_type(): void
    {
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

        $eventData = [
            'name' => 'Wedding Event',
            'eventType' => EventTypeEnum::Wedding->value,
            'celebrants' => [
                'bride' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Doe',
                ],
                'groom' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ],
            'segments' => [
                'wedding' => [
                    'date' => '2024-12-25',
                    'startTime' => '14:00:00',
                    'endTime' => '16:00:00',
                    'address' => [
                        'line1' => '123 Main St',
                        'city' => 'New York',
                        'state' => 'NY',
                        'zip' => '10001',
                    ],
                ],
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

        $this->assertDatabaseHas('event_checklist_groups', [
            'event_id' => $event->id,
            'name' => 'Wedding Tasks',
            'event_type' => 'Wedding',
        ]);

        $this->assertDatabaseMissing('event_checklist_groups', [
            'event_id' => $event->id,
            'name' => 'Birthday Tasks',
        ]);

        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();

        $this->assertDatabaseHas('event_checklists', [
            'event_checklist_group_id' => $eventGroup->id,
            'name' => 'Wedding Task',
        ]);

        $this->assertDatabaseMissing('event_checklists', [
            'name' => 'Birthday Task',
        ]);
    }

    public function test_checklist_due_date_calculated_with_days_frequency(): void
    {
        $eventDate = now()->addDays(30);

        $templateGroup = SupplierTemplateChecklistGroup::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Pre-Event Tasks',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierTemplateChecklist::create([
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Task 7 days before',
            'description' => 'Complete 7 days before event',
            'frequency_days' => 7,
            'frequency_type' => ChecklistFrequencyTypeEnum::Days->value,
            'frequency_anchor' => FrequencyAnchorEnum::BeforeEvent->value,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $createdAt = now();
        $eventData = [
            'name' => 'Birthday Party',
            'description' => 'Test event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => $eventDate->format('Y-m-d'),
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();
        $checklist = EventChecklist::where('event_checklist_group_id', $eventGroup->id)->first();

        $expectedDueDate = $eventDate->copy()->subDays(7)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $checklist->due_date->format('Y-m-d'));
    }

    public function test_checklist_due_date_calculated_with_weeks_frequency(): void
    {
        $eventDate = now()->addWeeks(8);

        $templateGroup = SupplierTemplateChecklistGroup::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Pre-Event Tasks',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierTemplateChecklist::create([
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Task 2 weeks before',
            'description' => 'Complete 2 weeks before event',
            'frequency_days' => 2,
            'frequency_type' => ChecklistFrequencyTypeEnum::Weeks->value,
            'frequency_anchor' => FrequencyAnchorEnum::BeforeEvent->value,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $createdAt = now();
        $eventData = [
            'name' => 'Birthday Party',
            'description' => 'Test event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => $eventDate->format('Y-m-d'),
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();
        $checklist = EventChecklist::where('event_checklist_group_id', $eventGroup->id)->first();

        $expectedDueDate = $eventDate->copy()->subWeeks(2)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $checklist->due_date->format('Y-m-d'));
    }

    public function test_checklist_due_date_calculated_with_months_frequency(): void
    {
        $eventDate = now()->addMonths(6);

        $templateGroup = SupplierTemplateChecklistGroup::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Pre-Event Tasks',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierTemplateChecklist::create([
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Task 2 months before',
            'description' => 'Complete 2 months before event',
            'frequency_days' => 2,
            'frequency_type' => ChecklistFrequencyTypeEnum::Months->value,
            'frequency_anchor' => FrequencyAnchorEnum::BeforeEvent->value,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $createdAt = now();
        $eventData = [
            'name' => 'Birthday Party',
            'description' => 'Test event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => $eventDate->format('Y-m-d'),
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();
        $checklist = EventChecklist::where('event_checklist_group_id', $eventGroup->id)->first();

        $expectedDueDate = $eventDate->copy()->subMonths(2)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $checklist->due_date->format('Y-m-d'));
    }

    public function test_checklist_due_date_calculated_with_after_creation_days(): void
    {
        $eventDate = now()->addMonths(6);

        $templateGroup = SupplierTemplateChecklistGroup::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Post-Creation Tasks',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierTemplateChecklist::create([
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Task 3 days after creation',
            'description' => 'Complete 3 days after event creation',
            'frequency_days' => 3,
            'frequency_type' => ChecklistFrequencyTypeEnum::Days->value,
            'frequency_anchor' => FrequencyAnchorEnum::AfterCreation->value,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $createdAt = now();
        $eventData = [
            'name' => 'Birthday Party',
            'description' => 'Test event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => $eventDate->format('Y-m-d'),
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();
        $checklist = EventChecklist::where('event_checklist_group_id', $eventGroup->id)->first();

        $expectedDueDate = now()->addDays(3)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $checklist->due_date->format('Y-m-d'));
    }

    public function test_checklist_due_date_calculated_with_after_creation_weeks(): void
    {
        $eventDate = now()->addMonths(6);

        $templateGroup = SupplierTemplateChecklistGroup::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Post-Creation Tasks',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierTemplateChecklist::create([
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Task 1 week after creation',
            'description' => 'Complete 1 week after event creation',
            'frequency_days' => 1,
            'frequency_type' => ChecklistFrequencyTypeEnum::Weeks->value,
            'frequency_anchor' => FrequencyAnchorEnum::AfterCreation->value,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $createdAt = now();
        $eventData = [
            'name' => 'Birthday Party',
            'description' => 'Test event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => $eventDate->format('Y-m-d'),
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();
        $checklist = EventChecklist::where('event_checklist_group_id', $eventGroup->id)->first();

        $expectedDueDate = now()->addWeeks(1)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $checklist->due_date->format('Y-m-d'));
    }

    public function test_checklist_due_date_calculated_with_after_creation_months(): void
    {
        $eventDate = now()->addMonths(6);

        $templateGroup = SupplierTemplateChecklistGroup::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Post-Creation Tasks',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        SupplierTemplateChecklist::create([
            'supplier_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Task 1 month after creation',
            'description' => 'Complete 1 month after event creation',
            'frequency_days' => 1,
            'frequency_type' => ChecklistFrequencyTypeEnum::Months->value,
            'frequency_anchor' => FrequencyAnchorEnum::AfterCreation->value,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $createdAt = now();
        $eventData = [
            'name' => 'Birthday Party',
            'description' => 'Test event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => $eventDate->format('Y-m-d'),
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
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
        $eventGroup = EventChecklistGroup::where('event_id', $event->id)->first();
        $checklist = EventChecklist::where('event_checklist_group_id', $eventGroup->id)->first();

        $expectedDueDate = now()->addMonths(1)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $checklist->due_date->format('Y-m-d'));
    }
}
