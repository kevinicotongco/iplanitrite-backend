<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventGuestStatusEnum;
use App\Models\Event;
use App\Models\EventFloorPlan;
use App\Models\EventGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesFloorPlanFixtures;
use Tests\TestCase;

class ClientEventGuestSeatAssignmentTest extends TestCase
{
    use CreatesFloorPlanFixtures, RefreshDatabase;

    private Event $event;
    private EventFloorPlan $floorPlan;
    private EventGuest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventPricingFixtures();

        $this->event = $this->createEvent();
        $this->floorPlan = $this->createFloorPlan($this->event);
        $this->attachClientToEvent($this->event);
        $this->guest = $this->createGuest($this->event, 'Ana');
    }

    private function url(EventGuest $guest, string $action, ?Event $event = null, ?string $groupId = null): string
    {
        return '/api/clients/events/' . ($event ?? $this->event)->id
            . '/guest-groups/' . ($groupId ?? $guest->event_guest_group_id)
            . '/guests/' . $guest->id . '/' . $action;
    }

    private function seat(int $number, string $tableId = self::TABLE_ONE): string
    {
        return 'seat_' . $tableId . '_' . $number;
    }

    // PUT assign

    public function test_client_can_assign_guest_to_seat(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), ['seatId' => $this->seat(2)])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_seats', [
            'event_floor_plan_id' => $this->floorPlan->id,
            'table_id' => self::TABLE_ONE,
            'seat_id' => $this->seat(2),
            'event_guest_id' => $this->guest->id,
            'created_by' => $this->client->id,
        ]);
    }

    public function test_assign_finds_table_from_seat_on_second_table(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), ['seatId' => $this->seat(1, self::TABLE_TWO)])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_seats', [
            'table_id' => self::TABLE_TWO,
            'seat_id' => $this->seat(1, self::TABLE_TWO),
            'event_guest_id' => $this->guest->id,
        ]);
    }

    public function test_assign_works_for_pending_requested_and_approved_guests(): void
    {
        $seatIds = [$this->seat(1), $this->seat(2), $this->seat(3)];
        $statuses = [EventGuestStatusEnum::Pending, EventGuestStatusEnum::Requested, EventGuestStatusEnum::Approved];

        foreach ($statuses as $index => $status) {
            $guest = $this->createGuest($this->event, 'G' . $index);
            $guest->update(['status' => $status]);

            $this->actingAs($this->client, 'client')
                ->putJson($this->url($guest, 'assign'), ['seatId' => $seatIds[$index]])
                ->assertStatus(200);

            $this->assertDatabaseHas('event_seats', ['event_guest_id' => $guest->id]);
        }
    }

    public function test_assign_rejects_denied_and_cancelled_guests(): void
    {
        foreach ([EventGuestStatusEnum::Denied, EventGuestStatusEnum::Cancelled] as $status) {
            $guest = $this->createGuest($this->event, $status->value);
            $guest->update(['status' => $status]);

            $this->actingAs($this->client, 'client')
                ->putJson($this->url($guest, 'assign'), ['seatId' => $this->seat(1)])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['status']);

            $this->assertDatabaseMissing('event_seats', ['event_guest_id' => $guest->id]);
        }
    }

    public function test_assign_moves_guest_and_removes_old_seat(): void
    {
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, $this->seat(1), $this->guest);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), ['seatId' => $this->seat(1, self::TABLE_TWO)])
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_seats', ['seat_id' => $this->seat(1)]);
        $this->assertDatabaseHas('event_seats', [
            'seat_id' => $this->seat(1, self::TABLE_TWO),
            'event_guest_id' => $this->guest->id,
        ]);
        $this->assertDatabaseCount('event_seats', 1);
    }

    public function test_assign_to_current_seat_is_a_no_op(): void
    {
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, $this->seat(1), $this->guest);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), ['seatId' => $this->seat(1)])
            ->assertStatus(200);

        $this->assertDatabaseCount('event_seats', 1);
    }

    public function test_assign_rejects_seat_taken_by_another_guest(): void
    {
        $other = $this->createGuest($this->event, 'Other');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, $this->seat(1), $other);
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, $this->seat(2), $this->guest);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), ['seatId' => $this->seat(1)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['seatId']);

        $this->assertDatabaseHas('event_seats', ['seat_id' => $this->seat(1), 'event_guest_id' => $other->id]);
        $this->assertDatabaseHas('event_seats', ['seat_id' => $this->seat(2), 'event_guest_id' => $this->guest->id]);
    }

    public function test_assign_rejects_seat_missing_from_canvas(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), ['seatId' => 'seat_unknown'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['seatId']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_validates_body(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['seatId']);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign'), ['seatId' => ['x']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['seatId']);
    }

    public function test_assign_returns_404_for_missing_guest_group_and_event(): void
    {
        $body = ['seatId' => $this->seat(1)];

        $missingGuest = clone $this->guest;
        $missingGuest->id = Str::uuid()->toString();
        $this->actingAs($this->client, 'client')->putJson($this->url($missingGuest, 'assign'), $body)->assertStatus(404);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'assign', null, Str::uuid()->toString()), $body)
            ->assertStatus(404);

        $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/events/' . Str::uuid()->toString() . '/guest-groups/' . $this->guest->event_guest_group_id . '/guests/' . $this->guest->id . '/assign', $body)
            ->assertStatus(404);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_returns_404_for_guest_of_another_event_or_group(): void
    {
        $otherEvent = $this->createEvent();
        $this->attachClientToEvent($otherEvent);
        $this->createFloorPlan($otherEvent);
        $foreignGuest = $this->createGuest($otherEvent, 'Foreign');

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($foreignGuest, 'assign', $this->event, $this->guest->event_guest_group_id), ['seatId' => $this->seat(1)])
            ->assertStatus(404);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_returns_404_for_event_client_is_not_linked_to(): void
    {
        $otherEvent = $this->createEvent();
        $this->createFloorPlan($otherEvent);
        $otherGuest = $this->createGuest($otherEvent, 'Other');

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($otherGuest, 'assign', $otherEvent), ['seatId' => $this->seat(1)])
            ->assertStatus(404);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_returns_404_when_event_has_no_floor_plan(): void
    {
        $event = $this->createEvent();
        $this->attachClientToEvent($event);
        $guest = $this->createGuest($event, 'NoPlan');

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($guest, 'assign', $event), ['seatId' => $this->seat(1)])
            ->assertStatus(404);
    }

    public function test_assign_requires_client_authentication(): void
    {
        $url = $this->url($this->guest, 'assign');
        $body = ['seatId' => $this->seat(1)];

        $this->putJson($url, $body)->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->putJson($url, $body)->assertStatus(401);

        $this->assertDatabaseCount('event_seats', 0);
    }

    // PUT unassign

    public function test_client_can_unassign_seated_guest(): void
    {
        $other = $this->createGuest($this->event, 'Other');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, $this->seat(1), $this->guest);
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, $this->seat(2), $other);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'unassign'))
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_seats', ['event_guest_id' => $this->guest->id]);
        $this->assertDatabaseHas('event_seats', ['event_guest_id' => $other->id]);
    }

    public function test_unassign_guest_without_seat_succeeds(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'unassign'))
            ->assertStatus(200);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_unassign_returns_404_for_missing_guest_group_and_event(): void
    {
        $missingGuest = clone $this->guest;
        $missingGuest->id = Str::uuid()->toString();
        $this->actingAs($this->client, 'client')->putJson($this->url($missingGuest, 'unassign'))->assertStatus(404);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($this->guest, 'unassign', null, Str::uuid()->toString()))
            ->assertStatus(404);

        $this->actingAs($this->client, 'client')
            ->putJson('/api/clients/events/' . Str::uuid()->toString() . '/guest-groups/' . $this->guest->event_guest_group_id . '/guests/' . $this->guest->id . '/unassign')
            ->assertStatus(404);
    }

    public function test_unassign_returns_404_for_event_client_is_not_linked_to(): void
    {
        $otherEvent = $this->createEvent();
        $this->createFloorPlan($otherEvent);
        $otherGuest = $this->createGuest($otherEvent, 'Other');
        $this->seatGuest($this->createFloorPlan($this->createEvent()), self::TABLE_ONE, $this->seat(1), $otherGuest);

        $this->actingAs($this->client, 'client')
            ->putJson($this->url($otherGuest, 'unassign', $otherEvent))
            ->assertStatus(404);

        $this->assertDatabaseHas('event_seats', ['event_guest_id' => $otherGuest->id]);
    }

    public function test_unassign_requires_client_authentication(): void
    {
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, $this->seat(1), $this->guest);
        $url = $this->url($this->guest, 'unassign');

        $this->putJson($url)->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->putJson($url)->assertStatus(401);

        $this->assertDatabaseHas('event_seats', ['event_guest_id' => $this->guest->id]);
    }
}
