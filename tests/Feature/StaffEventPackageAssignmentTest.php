<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventTypeEnum;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventPricingFixtures;
use Tests\TestCase;

class StaffEventPackageAssignmentTest extends TestCase
{
    use CreatesEventPricingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->setUpEventPricingFixtures();
    }

    /**
     * @return array<string, mixed>
     */
    private function createEventPayload(string $eventPackageId, EventTypeEnum $eventType = EventTypeEnum::Birthday): array
    {
        return [
            'name' => 'John\'s Birthday',
            'description' => 'A fun birthday party',
            'eventType' => $eventType->value,
            'eventPackageId' => $eventPackageId,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2026-12-25',
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
                    'email' => 'new-client@example.com',
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function updateEventPayload(string $eventPackageId, EventTypeEnum $eventType = EventTypeEnum::Birthday): array
    {
        return [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'status' => 'Ongoing',
            'eventType' => $eventType->value,
            'eventPackageId' => $eventPackageId,
            'celebrant' => [
                'firstName' => 'Updated',
                'lastName' => 'Celebrant',
            ],
        ];
    }

    // POST /api/staff/events

    public function test_creating_event_assigns_package_and_creates_initial_price(): void
    {
        $package = $this->createPackage(EventTypeEnum::Birthday, 'Party Package', 25000.5);

        $this->withToken($this->token)
            ->postJson('/api/staff/events', $this->createEventPayload($package->id))
            ->assertStatus(201);

        $event = Event::firstOrFail();

        $this->assertSame($package->id, $event->event_package_id);
        $this->assertDatabaseCount('event_prices', 1);
        $this->assertDatabaseHas('event_prices', [
            'account_id' => $this->account->id,
            'event_id' => $event->id,
            'name' => 'Party Package',
            'cost_price' => 0,
            'retail_price' => 25000.5,
            'sort_order' => 0,
            'supplier_id' => null,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_events_list_includes_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Birthday, 'Party Package', 25000.5);

        $this->withToken($this->token)
            ->postJson('/api/staff/events', $this->createEventPayload($package->id))
            ->assertStatus(201);

        $this->withToken($this->token)
            ->getJson('/api/staff/events')
            ->assertStatus(200)
            ->assertJsonPath('0.package', [
                'id' => $package->id,
                'name' => 'Party Package',
                'description' => 'Party Package description',
                'price' => 25000.5,
            ]);
    }

    public function test_create_event_requires_valid_event_package_id(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/staff/events', $this->createEventPayload('not-a-uuid'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eventPackageId']);

        $payload = $this->createEventPayload(Str::uuid()->toString());
        unset($payload['eventPackageId']);

        $this->withToken($this->token)
            ->postJson('/api/staff/events', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eventPackageId']);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_create_event_rejects_unassignable_packages(): void
    {
        $deleted = $this->createPackage(EventTypeEnum::Birthday);
        $deleted->delete();

        $invalidPackageIds = [
            Str::uuid()->toString(),
            $this->createPackage(EventTypeEnum::Birthday, account: $this->otherAccount)->id,
            $this->createPackage(EventTypeEnum::Wedding)->id,
            $deleted->id,
        ];

        foreach ($invalidPackageIds as $eventPackageId) {
            $this->withToken($this->token)
                ->postJson('/api/staff/events', $this->createEventPayload($eventPackageId))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['eventPackageId']);
        }

        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('event_prices', 0);
        $this->assertDatabaseCount('celebrants', 0);
    }

    // PUT /api/staff/events/{id}

    public function test_changing_package_updates_retail_price_of_first_created_price_only(): void
    {
        $oldPackage = $this->createPackage(EventTypeEnum::Birthday, 'Old Package', 1000);
        $newPackage = $this->createPackage(EventTypeEnum::Birthday, 'New Package', 3000.75);
        $event = $this->createEvent($oldPackage);
        $packagePrice = $this->createPrice($event, [
            'name' => 'Old Package',
            'cost_price' => 200,
            'retail_price' => 1000,
            'sort_order' => 5,
        ]);
        $otherPrice = $this->createPrice($event, ['retail_price' => 777, 'sort_order' => 0]);

        $this->withToken($this->token)
            ->putJson("/api/staff/events/{$event->id}", $this->updateEventPayload($newPackage->id))
            ->assertStatus(200);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_package_id' => $newPackage->id]);
        $this->assertDatabaseHas('event_prices', [
            'id' => $packagePrice->id,
            'name' => 'Old Package',
            'cost_price' => 200,
            'retail_price' => 3000.75,
            'sort_order' => 5,
            'updated_by' => $this->staff->id,
        ]);
        $this->assertDatabaseHas('event_prices', ['id' => $otherPrice->id, 'retail_price' => 777]);
    }

    public function test_keeping_same_package_leaves_prices_untouched(): void
    {
        $package = $this->createPackage(EventTypeEnum::Birthday, 'Package', 1000);
        $event = $this->createEvent($package);
        $packagePrice = $this->createPrice($event, ['retail_price' => 1500]);

        $this->withToken($this->token)
            ->putJson("/api/staff/events/{$event->id}", $this->updateEventPayload($package->id))
            ->assertStatus(200);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_package_id' => $package->id]);
        $this->assertDatabaseHas('event_prices', ['id' => $packagePrice->id, 'retail_price' => 1500]);
    }

    public function test_event_can_be_updated_while_keeping_its_deleted_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Birthday, 'Legacy Package', 1000);
        $event = $this->createEvent($package);
        $package->delete();

        $this->withToken($this->token)
            ->putJson("/api/staff/events/{$event->id}", $this->updateEventPayload($package->id))
            ->assertStatus(200);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Name',
            'event_package_id' => $package->id,
        ]);
    }

    public function test_update_event_rejects_switching_to_unassignable_package(): void
    {
        $currentPackage = $this->createPackage(EventTypeEnum::Birthday);
        $event = $this->createEvent($currentPackage);
        $deleted = $this->createPackage(EventTypeEnum::Birthday);
        $deleted->delete();

        $invalidPackageIds = [
            Str::uuid()->toString(),
            $this->createPackage(EventTypeEnum::Birthday, account: $this->otherAccount)->id,
            $this->createPackage(EventTypeEnum::Wedding)->id,
            $deleted->id,
        ];

        foreach ($invalidPackageIds as $eventPackageId) {
            $this->withToken($this->token)
                ->putJson("/api/staff/events/{$event->id}", $this->updateEventPayload($eventPackageId))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['eventPackageId']);
        }

        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_package_id' => $currentPackage->id]);
    }

    public function test_update_event_rejects_current_package_when_event_type_changes(): void
    {
        $package = $this->createPackage(EventTypeEnum::Birthday);
        $event = $this->createEvent($package);

        $this->withToken($this->token)
            ->putJson("/api/staff/events/{$event->id}", $this->updateEventPayload($package->id, EventTypeEnum::Debut))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eventPackageId']);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_type' => EventTypeEnum::Birthday->value]);
    }

    public function test_update_event_requires_event_package_id(): void
    {
        $event = $this->createEvent();
        $payload = $this->updateEventPayload(Str::uuid()->toString());
        unset($payload['eventPackageId']);

        $this->withToken($this->token)
            ->putJson("/api/staff/events/{$event->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eventPackageId']);
    }

    public function test_update_event_from_other_account_returns_404(): void
    {
        $otherPackage = $this->createPackage(EventTypeEnum::Birthday, account: $this->otherAccount);
        $otherEvent = $this->createEvent($otherPackage, $this->otherAccount);

        $this->withToken($this->token)
            ->putJson("/api/staff/events/{$otherEvent->id}", $this->updateEventPayload($otherPackage->id))
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_change_event_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Birthday);
        $event = $this->createEvent($package);
        $newPackage = $this->createPackage(EventTypeEnum::Birthday);

        $this->putJson("/api/staff/events/{$event->id}", $this->updateEventPayload($newPackage->id))
            ->assertStatus(401);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_package_id' => $package->id]);
    }
}
