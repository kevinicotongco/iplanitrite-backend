<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventFloorPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesFloorPlanFixtures;
use Tests\TestCase;

class ClientEventFloorPlanTest extends TestCase
{
    use CreatesFloorPlanFixtures, RefreshDatabase;

    private Event $event;
    private EventFloorPlan $floorPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventPricingFixtures();

        $this->event = $this->createEvent();
        $this->floorPlan = $this->createFloorPlan($this->event);
        $this->attachClientToEvent($this->event);
    }

    private function showUrl(?Event $event = null): string
    {
        return '/api/clients/events/' . ($event ?? $this->event)->id . '/floor-plan';
    }

    private function seatsUrl(string $mode, string $tableId = self::TABLE_ONE, ?Event $event = null, ?EventFloorPlan $floorPlan = null): string
    {
        return '/api/clients/events/' . ($event ?? $this->event)->id
            . '/floor-plans/' . ($floorPlan ?? $this->floorPlan)->id
            . '/seats/' . $mode . '/' . $tableId;
    }

    /**
     * @param list<string> $guestIds
     * @return list<array<string, string>>
     */
    private function byTablePayload(array $guestIds): array
    {
        return array_map(fn(string $id): array => ['eventGuestId' => $id], $guestIds);
    }

    /**
     * @return list<string>
     */
    private function createGuestIds(int $count, ?Event $event = null): array
    {
        return array_map(
            fn(int $number): string => $this->createGuest($event ?? $this->event, 'Guest' . $number)->id,
            range(1, $count),
        );
    }

    private function otherClient(): Client
    {
        return Client::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'email' => 'other-client@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Other',
            'last_name' => 'Client',
        ]);
    }

    // GET /api/clients/events/{eventId}/floor-plan

    public function test_client_can_view_floor_plan_with_seats(): void
    {
        $guest = $this->createGuest($this->event);
        $this->seatGuest($this->floorPlan, self::TABLE_TWO, 'seat_tbl_2_1', $guest);

        $this->actingAs($this->client, 'client')->getJson($this->showUrl())
            ->assertStatus(200)
            ->assertJsonPath('id', $this->floorPlan->id)
            ->assertJsonPath('eventId', $this->event->id)
            ->assertJsonPath('canvas.objects.1.id', self::TABLE_TWO)
            ->assertJsonCount(1, 'seats')
            ->assertJsonPath('seats.0.eventGuestId', $guest->id);
    }

    public function test_client_cannot_view_floor_plan_of_event_they_are_not_linked_to(): void
    {
        $otherEvent = $this->createEvent();
        $this->createFloorPlan($otherEvent);

        $this->actingAs($this->client, 'client')->getJson($this->showUrl($otherEvent))->assertStatus(404);
    }

    public function test_client_cannot_view_floor_plan_after_being_removed_from_event(): void
    {
        DB::table('event_clients')->update(['deleted_at' => now()]);

        $this->actingAs($this->client, 'client')->getJson($this->showUrl())->assertStatus(404);
    }

    public function test_view_floor_plan_returns_404_for_missing_event(): void
    {
        $this->actingAs($this->client, 'client')
            ->getJson('/api/clients/events/' . Str::uuid() . '/floor-plan')
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_view_floor_plan(): void
    {
        $this->getJson($this->showUrl())->assertStatus(401);
    }

    public function test_staff_cannot_view_floor_plan_through_client_route(): void
    {
        $this->withToken($this->token)->getJson($this->showUrl())->assertStatus(401);
    }

    // PUT /api/clients/events/{eventId}/floor-plans/{floorPlanId}/seats/by-table/{tableId}

    public function test_client_can_assign_guests_to_table_randomly(): void
    {
        $guestIds = $this->createGuestIds(4);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table'), $this->byTablePayload($guestIds))
            ->assertStatus(200)
            ->assertExactJson([]);

        $seats = $this->floorPlan->seats()->get();

        $this->assertCount(4, $seats);
        $this->assertEqualsCanonicalizing($guestIds, $seats->pluck('event_guest_id')->all());
        $this->assertEqualsCanonicalizing($this->seatIds(self::TABLE_ONE, 4), $seats->pluck('seat_id')->all());
        $this->assertSame([self::TABLE_ONE], $seats->pluck('table_id')->unique()->values()->all());
        $this->assertSame([$this->client->id], $seats->pluck('created_by')->unique()->values()->all());
        $this->assertSame([$this->client->id], $seats->pluck('updated_by')->unique()->values()->all());
    }

    public function test_assigning_by_table_replaces_existing_seats_of_the_table(): void
    {
        $oldGuest = $this->createGuest($this->event, 'Old');
        $this->seatGuest($this->floorPlan, self::TABLE_TWO, 'seat_tbl_2_1', $oldGuest);
        $untouched = $this->createGuest($this->event, 'Untouched');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_1', $untouched);

        $guestIds = $this->createGuestIds(2);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload($guestIds))
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_seats', ['event_guest_id' => $oldGuest->id]);
        $this->assertDatabaseHas('event_seats', ['event_guest_id' => $untouched->id, 'table_id' => self::TABLE_ONE]);
        $this->assertSame(2, $this->floorPlan->seats()->where('table_id', self::TABLE_TWO)->count());
    }

    public function test_assigning_by_table_moves_guest_seated_at_another_table(): void
    {
        $moving = $this->createGuest($this->event, 'Moving');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_3', $moving);
        $other = $this->createGuest($this->event, 'Other');

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload([$moving->id, $other->id]))
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_seats', ['table_id' => self::TABLE_ONE]);
        $this->assertSame(1, $this->floorPlan->seats()->where('event_guest_id', $moving->id)->count());
        $this->assertSame(self::TABLE_TWO, $this->floorPlan->seats()->where('event_guest_id', $moving->id)->value('table_id'));
    }

    public function test_assign_by_table_requires_guest_count_to_match_seat_count(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table'), $this->byTablePayload($this->createGuestIds(3)))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guests']);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table'), $this->byTablePayload($this->createGuestIds(5)))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guests']);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guests']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_table_requires_valid_guest_ids(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), [['eventGuestId' => 'not-a-uuid'], []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.eventGuestId', '1.eventGuestId']);
    }

    public function test_assign_by_table_rejects_duplicate_guests(): void
    {
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload([$guest->id, $guest->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.eventGuestId', '1.eventGuestId']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_table_rejects_non_list_body(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), ['a' => ['eventGuestId' => (string) Str::uuid()]])
            ->assertStatus(422);
    }

    public function test_assign_by_table_rejects_nonexistent_guest(): void
    {
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload([$guest->id, (string) Str::uuid()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['1.eventGuestId']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_table_rejects_soft_deleted_guest(): void
    {
        $guest = $this->createGuest($this->event);
        $deleted = $this->createGuest($this->event, 'Deleted');
        $deleted->delete();

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload([$guest->id, $deleted->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['1.eventGuestId']);
    }

    public function test_assign_by_table_rejects_guest_of_another_event(): void
    {
        $guest = $this->createGuest($this->event);
        $foreign = $this->createGuest($this->createEvent(), 'Foreign');

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload([$guest->id, $foreign->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['1.eventGuestId']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_table_does_not_change_seats_when_validation_fails(): void
    {
        $seated = $this->createGuest($this->event, 'Seated');
        $this->seatGuest($this->floorPlan, self::TABLE_TWO, 'seat_tbl_2_1', $seated);
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload([$guest->id, (string) Str::uuid()]))
            ->assertStatus(422);

        $this->assertDatabaseHas('event_seats', ['event_guest_id' => $seated->id]);
    }

    public function test_assign_by_table_returns_404_for_unknown_table(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', 'tbl_unknown'), [])
            ->assertStatus(404);
    }

    public function test_assign_by_table_returns_404_for_non_table_object(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::STAGE), [])
            ->assertStatus(404);
    }

    public function test_assign_by_table_returns_404_for_floor_plan_of_another_event(): void
    {
        $otherPlan = $this->createFloorPlan($this->createEvent());

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', floorPlan: $otherPlan), $this->byTablePayload($this->createGuestIds(4)))
            ->assertStatus(404);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_table_returns_404_for_missing_floor_plan(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson(
                '/api/clients/events/' . $this->event->id . '/floor-plans/' . Str::uuid() . '/seats/by-table/' . self::TABLE_ONE,
                [],
            )
            ->assertStatus(404);
    }

    public function test_assign_by_table_returns_404_for_event_client_is_not_linked_to(): void
    {
        $otherEvent = $this->createEvent();
        $otherPlan = $this->createFloorPlan($otherEvent);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO, $otherEvent, $otherPlan), $this->byTablePayload($this->createGuestIds(2, $otherEvent)))
            ->assertStatus(404);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_table_returns_404_for_client_not_linked_to_event(): void
    {
        $this->actingAs($this->otherClient(), 'client')
            ->putJson($this->seatsUrl('by-table', self::TABLE_TWO), $this->byTablePayload($this->createGuestIds(2)))
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_assign_by_table(): void
    {
        $this->putJson($this->seatsUrl('by-table'), [])->assertStatus(401);
    }

    public function test_staff_cannot_assign_by_table(): void
    {
        $this->withToken($this->token)->putJson($this->seatsUrl('by-table'), [])->assertStatus(401);
    }

    // PUT /api/clients/events/{eventId}/floor-plans/{floorPlanId}/seats/by-seats/{tableId}

    public function test_client_can_assign_guests_to_specific_seats(): void
    {
        [$guestOne, $guestTwo] = $this->createGuestIds(2);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [
                ['eventGuestId' => $guestOne, 'seatId' => 'seat_tbl_1_2'],
                ['eventGuestId' => $guestTwo, 'seatId' => 'seat_tbl_1_4'],
            ])
            ->assertStatus(200)
            ->assertExactJson([]);

        $this->assertDatabaseHas('event_seats', [
            'event_floor_plan_id' => $this->floorPlan->id,
            'table_id' => self::TABLE_ONE,
            'seat_id' => 'seat_tbl_1_2',
            'event_guest_id' => $guestOne,
            'created_by' => $this->client->id,
            'updated_by' => $this->client->id,
        ]);
        $this->assertDatabaseHas('event_seats', ['seat_id' => 'seat_tbl_1_4', 'event_guest_id' => $guestTwo]);
        $this->assertDatabaseCount('event_seats', 2);
    }

    public function test_assigning_by_seat_keeps_seats_not_in_body(): void
    {
        $keeper = $this->createGuest($this->event, 'Keeper');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_1', $keeper);
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $guest->id, 'seatId' => 'seat_tbl_1_2']])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_seats', ['seat_id' => 'seat_tbl_1_1', 'event_guest_id' => $keeper->id]);
        $this->assertDatabaseCount('event_seats', 2);
    }

    public function test_assigning_by_seat_replaces_guest_in_taken_seat(): void
    {
        $previous = $this->createGuest($this->event, 'Previous');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_1', $previous);
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $guest->id, 'seatId' => 'seat_tbl_1_1']])
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_seats', ['event_guest_id' => $previous->id]);
        $this->assertDatabaseHas('event_seats', ['seat_id' => 'seat_tbl_1_1', 'event_guest_id' => $guest->id]);
        $this->assertDatabaseCount('event_seats', 1);
    }

    public function test_assigning_by_seat_moves_guest_seated_elsewhere(): void
    {
        $guest = $this->createGuest($this->event);
        $this->seatGuest($this->floorPlan, self::TABLE_TWO, 'seat_tbl_2_2', $guest);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $guest->id, 'seatId' => 'seat_tbl_1_3']])
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_seats', ['table_id' => self::TABLE_TWO]);
        $this->assertDatabaseHas('event_seats', ['table_id' => self::TABLE_ONE, 'seat_id' => 'seat_tbl_1_3', 'event_guest_id' => $guest->id]);
        $this->assertDatabaseCount('event_seats', 1);
    }

    public function test_assign_by_seat_with_empty_list_changes_nothing(): void
    {
        $guest = $this->createGuest($this->event);
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_1', $guest);

        $this->actingAs($this->client, 'client')->putJson($this->seatsUrl('by-seats'), [])->assertStatus(200);

        $this->assertDatabaseCount('event_seats', 1);
    }

    public function test_assign_by_seat_requires_fields(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [[]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.eventGuestId', '0.seatId']);
    }

    public function test_assign_by_seat_rejects_seat_of_another_table(): void
    {
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $guest->id, 'seatId' => 'seat_tbl_2_1']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.seatId']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_seat_rejects_unknown_seat(): void
    {
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $guest->id, 'seatId' => 'seat_nope']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.seatId']);
    }

    public function test_assign_by_seat_rejects_duplicate_seats_and_guests(): void
    {
        [$guestOne, $guestTwo] = $this->createGuestIds(2);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [
                ['eventGuestId' => $guestOne, 'seatId' => 'seat_tbl_1_1'],
                ['eventGuestId' => $guestTwo, 'seatId' => 'seat_tbl_1_1'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.seatId', '1.seatId']);

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [
                ['eventGuestId' => $guestOne, 'seatId' => 'seat_tbl_1_1'],
                ['eventGuestId' => $guestOne, 'seatId' => 'seat_tbl_1_2'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.eventGuestId', '1.eventGuestId']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_seat_rejects_nonexistent_guest(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => (string) Str::uuid(), 'seatId' => 'seat_tbl_1_1']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.eventGuestId']);
    }

    public function test_assign_by_seat_rejects_soft_deleted_guest(): void
    {
        $deleted = $this->createGuest($this->event, 'Deleted');
        $deleted->delete();

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $deleted->id, 'seatId' => 'seat_tbl_1_1']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.eventGuestId']);
    }

    public function test_assign_by_seat_rejects_guest_of_another_event(): void
    {
        $foreign = $this->createGuest($this->createEvent(), 'Foreign');

        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $foreign->id, 'seatId' => 'seat_tbl_1_1']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.eventGuestId']);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_assign_by_seat_returns_404_for_unknown_table(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats', 'tbl_unknown'), [])
            ->assertStatus(404);
    }

    public function test_assign_by_seat_returns_404_for_non_table_object(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->seatsUrl('by-seats', self::STAGE), [])
            ->assertStatus(404);
    }

    public function test_assign_by_seat_returns_404_for_floor_plan_of_another_event(): void
    {
        $otherPlan = $this->createFloorPlan($this->createEvent());
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->client, 'client')
            ->putJson(
                $this->seatsUrl('by-seats', floorPlan: $otherPlan),
                [['eventGuestId' => $guest->id, 'seatId' => 'seat_tbl_1_1']],
            )
            ->assertStatus(404);
    }

    public function test_assign_by_seat_returns_404_for_client_not_linked_to_event(): void
    {
        $guest = $this->createGuest($this->event);

        $this->actingAs($this->otherClient(), 'client')
            ->putJson($this->seatsUrl('by-seats'), [['eventGuestId' => $guest->id, 'seatId' => 'seat_tbl_1_1']])
            ->assertStatus(404);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_unauthenticated_user_cannot_assign_by_seat(): void
    {
        $this->putJson($this->seatsUrl('by-seats'), [])->assertStatus(401);
    }

    public function test_staff_cannot_assign_by_seat(): void
    {
        $this->withToken($this->token)->putJson($this->seatsUrl('by-seats'), [])->assertStatus(401);
    }
}
