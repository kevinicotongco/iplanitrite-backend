<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventFloorPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesFloorPlanFixtures;
use Tests\TestCase;

class StaffEventFloorPlanTest extends TestCase
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
    }

    private function showUrl(?Event $event = null): string
    {
        return '/api/staff/events/' . ($event ?? $this->event)->id . '/floor-plan';
    }

    private function updateUrl(?Event $event = null, ?EventFloorPlan $floorPlan = null): string
    {
        return '/api/staff/events/' . ($event ?? $this->event)->id
            . '/floor-plans/' . ($floorPlan ?? $this->floorPlan)->id;
    }

    // GET /api/staff/events/{eventId}/floor-plan

    public function test_staff_can_view_event_floor_plan_with_seats(): void
    {
        $guest = $this->createGuest($this->event);
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_1', $guest);

        $response = $this->withToken($this->token)->getJson($this->showUrl());

        $response->assertStatus(200)
            ->assertJsonPath('id', $this->floorPlan->id)
            ->assertJsonPath('eventId', $this->event->id)
            ->assertJsonPath('canvas.version', '1.0')
            ->assertJsonPath('canvas.objects.0.id', self::TABLE_ONE)
            ->assertJsonCount(1, 'seats')
            ->assertJsonPath('seats.0.tableId', self::TABLE_ONE)
            ->assertJsonPath('seats.0.seatId', 'seat_tbl_1_1')
            ->assertJsonPath('seats.0.eventGuestId', $guest->id);
    }

    public function test_view_floor_plan_returns_404_when_event_has_no_floor_plan(): void
    {
        $this->withToken($this->token)->getJson($this->showUrl($this->createEvent()))->assertStatus(404);
    }

    public function test_view_floor_plan_returns_404_for_missing_event(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/staff/events/' . Str::uuid() . '/floor-plan')
            ->assertStatus(404);
    }

    public function test_view_floor_plan_returns_404_for_event_of_another_account(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);
        $this->createFloorPlan($otherEvent);

        $this->withToken($this->token)->getJson($this->showUrl($otherEvent))->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_view_floor_plan(): void
    {
        $this->getJson($this->showUrl())->assertStatus(401);
    }

    public function test_client_cannot_view_floor_plan_through_staff_route(): void
    {
        $this->actingAs($this->client, 'client')->getJson($this->showUrl())->assertStatus(401);
    }

    // PUT /api/staff/events/{eventId}/floor-plans/{floorPlanId}

    public function test_staff_can_update_floor_plan_canvas(): void
    {
        $canvas = $this->canvasWith([$this->tableObject('tbl_new', 6), $this->stageObject()]);

        $response = $this->withToken($this->token)->putJson($this->updateUrl(), ['canvas' => $canvas]);

        $response->assertStatus(200)->assertExactJson([]);

        $this->floorPlan->refresh();
        $this->assertEquals($canvas, $this->floorPlan->canvas_state);
        $this->assertSame($this->staff->id, $this->floorPlan->updated_by);
    }

    public function test_update_stores_every_canvas_property_and_keeps_numeric_types(): void
    {
        $table = $this->tableObject('tbl_new', 2, ['x' => 350.5, 'scaleX' => 1.25, 'subtitle' => null, 'notes' => null]);
        $canvas = $this->canvasWith([$table, $this->stageObject()]);

        $this->withToken($this->token)->putJson($this->updateUrl(), ['canvas' => $canvas])->assertStatus(200);

        $this->assertEquals($canvas, $this->floorPlan->refresh()->canvas_state);

        $this->withToken($this->token)->getJson($this->showUrl())
            ->assertJsonPath('canvas.objects.0.x', 350.5)
            ->assertJsonPath('canvas.objects.0.subtitle', null)
            ->assertJsonPath('canvas.objects.1.cornerRadius', 6)
            ->assertJsonPath('canvas.canvas.snapToGrid', true);
    }

    public function test_update_ignores_unknown_canvas_properties(): void
    {
        $table = $this->tableObject('tbl_new', 2, ['unknownProperty' => 'x']);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$table])])
            ->assertStatus(200);

        $this->assertArrayNotHasKey('unknownProperty', $this->floorPlan->refresh()->canvas_state['objects'][0]);
    }

    public function test_update_requires_every_table_property(): void
    {
        $required = [
            'label', 'x', 'y', 'radius', 'rotation', 'scaleX', 'scaleY', 'zIndex', 'fill', 'stroke', 'strokeWidth',
            'shadowColor', 'shadowBlur', 'shadowOffsetX', 'shadowOffsetY', 'seatCount', 'seatRadius', 'seatSpacing',
            'seats', 'shape', 'isLocked', 'isVisible', 'isSelectable', 'minSeats', 'maxSeats', 'subtitle', 'notes',
        ];
        $table = $this->tableObject('tbl_a', 2);

        foreach ($required as $property) {
            $payload = $table;
            unset($payload[$property]);

            $this->withToken($this->token)
                ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$payload])])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['canvas.objects.0.' . $property]);
        }
    }

    public function test_update_requires_every_fixture_property(): void
    {
        $required = [
            'label', 'x', 'y', 'width', 'height', 'rotation', 'scaleX', 'scaleY', 'zIndex', 'fill', 'stroke',
            'strokeWidth', 'cornerRadius', 'shape', 'isLocked', 'isVisible', 'isSelectable', 'subtitle', 'notes',
        ];

        foreach ($required as $property) {
            $payload = $this->stageObject();
            unset($payload[$property]);

            $this->withToken($this->token)
                ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$payload])])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['canvas.objects.0.' . $property]);
        }
    }

    public function test_update_requires_every_canvas_setting(): void
    {
        foreach (['width', 'height', 'zoom', 'gridSize', 'snapToGrid', 'backgroundColor'] as $setting) {
            $canvas = $this->canvasWith([]);
            unset($canvas['canvas'][$setting]);

            $this->withToken($this->token)
                ->putJson($this->updateUrl(), ['canvas' => $canvas])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['canvas.canvas.' . $setting]);
        }
    }

    public function test_update_rejects_invalid_enum_values(): void
    {
        $table = $this->tableObject('tbl_a', 2, ['type' => 'hexTable', 'shape' => 'triangle']);
        $fixture = $this->stageObject();
        $fixture['category'] = 'furniture';

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$table, $fixture])])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'canvas.objects.0.type',
                'canvas.objects.0.shape',
                'canvas.objects.1.category',
            ]);
    }

    public function test_update_rejects_wrong_property_types(): void
    {
        $table = $this->tableObject('tbl_a', 2, [
            'x' => '350',
            'zIndex' => 1.5,
            'isLocked' => 'false',
            'seatCount' => '2',
            'radius' => null,
        ]);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$table])])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'canvas.objects.0.x',
                'canvas.objects.0.zIndex',
                'canvas.objects.0.isLocked',
                'canvas.objects.0.seatCount',
                'canvas.objects.0.radius',
            ]);
    }

    public function test_update_rejects_non_list_seats(): void
    {
        $table = $this->tableObject('tbl_a', 2, ['seats' => ['a' => 'seat_a', 'b' => 'seat_b']]);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$table])])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas.objects.0.seats']);
    }

    public function test_removing_a_table_deletes_its_seats_only(): void
    {
        $guestOne = $this->createGuest($this->event, 'One');
        $guestTwo = $this->createGuest($this->event, 'Two');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_1', $guestOne);
        $this->seatGuest($this->floorPlan, self::TABLE_TWO, 'seat_tbl_2_1', $guestTwo);

        $canvas = $this->canvasWith([$this->tableObject(self::TABLE_TWO, 2), $this->stageObject()]);

        $this->withToken($this->token)->putJson($this->updateUrl(), ['canvas' => $canvas])->assertStatus(200);

        $this->assertDatabaseMissing('event_seats', ['table_id' => self::TABLE_ONE]);
        $this->assertDatabaseHas('event_seats', ['table_id' => self::TABLE_TWO, 'event_guest_id' => $guestTwo->id]);
        $this->assertDatabaseHas('event_guests', ['id' => $guestOne->id]);
    }

    public function test_shrinking_a_table_deletes_only_removed_seats(): void
    {
        $guestOne = $this->createGuest($this->event, 'One');
        $guestFour = $this->createGuest($this->event, 'Four');
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_1', $guestOne);
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_4', $guestFour);

        $canvas = $this->canvasWith([$this->tableObject(self::TABLE_ONE, 3), $this->tableObject(self::TABLE_TWO, 2)]);

        $this->withToken($this->token)->putJson($this->updateUrl(), ['canvas' => $canvas])->assertStatus(200);

        $this->assertDatabaseHas('event_seats', ['seat_id' => 'seat_tbl_1_1', 'event_guest_id' => $guestOne->id]);
        $this->assertDatabaseMissing('event_seats', ['seat_id' => 'seat_tbl_1_4']);
    }

    public function test_growing_a_table_keeps_existing_seats(): void
    {
        $guest = $this->createGuest($this->event);
        $this->seatGuest($this->floorPlan, self::TABLE_ONE, 'seat_tbl_1_2', $guest);

        $canvas = $this->canvasWith([$this->tableObject(self::TABLE_ONE, 8), $this->tableObject(self::TABLE_TWO, 2)]);

        $this->withToken($this->token)->putJson($this->updateUrl(), ['canvas' => $canvas])->assertStatus(200);

        $this->assertDatabaseHas('event_seats', ['seat_id' => 'seat_tbl_1_2', 'event_guest_id' => $guest->id]);
        $this->assertDatabaseCount('event_seats', 1);
    }

    public function test_changing_a_seat_id_deletes_the_old_assignment(): void
    {
        $guest = $this->createGuest($this->event);
        $this->seatGuest($this->floorPlan, self::TABLE_TWO, 'seat_tbl_2_1', $guest);

        $table = $this->tableObject(self::TABLE_TWO, 2, ['seats' => ['renamed_1', 'renamed_2']]);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$table])])
            ->assertStatus(200);

        $this->assertDatabaseCount('event_seats', 0);
    }

    public function test_update_does_not_delete_seats_of_other_floor_plans(): void
    {
        $otherEvent = $this->createEvent();
        $otherPlan = $this->createFloorPlan($otherEvent);
        $otherGuest = $this->createGuest($otherEvent);
        $this->seatGuest($otherPlan, self::TABLE_ONE, 'seat_tbl_1_1', $otherGuest);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([])])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_seats', ['event_floor_plan_id' => $otherPlan->id]);
    }

    public function test_update_requires_canvas(): void
    {
        $this->withToken($this->token)
            ->putJson($this->updateUrl(), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas']);
    }

    public function test_update_rejects_canvas_sent_as_string(): void
    {
        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => json_encode($this->defaultTestCanvas())])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas']);
    }

    public function test_update_requires_canvas_structure(): void
    {
        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => ['foo' => 'bar']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas.version', 'canvas.canvas', 'canvas.objects']);
    }

    public function test_update_requires_object_identifiers(): void
    {
        $canvas = $this->canvasWith([['label' => 'No id']]);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $canvas])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'canvas.objects.0.id',
                'canvas.objects.0.type',
                'canvas.objects.0.category',
            ]);
    }

    public function test_update_rejects_duplicate_object_ids(): void
    {
        $canvas = $this->canvasWith([$this->tableObject('tbl_dup', 2), $this->stageObject(),$this->tableObject('tbl_dup', 2)]);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $canvas])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas.objects.0.id', 'canvas.objects.2.id']);
    }

    public function test_update_rejects_seat_count_mismatch(): void
    {
        $canvas = $this->canvasWith([$this->tableObject('tbl_a', 4, ['seatCount' => 6])]);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $canvas])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas.objects.0.seatCount']);
    }

    public function test_update_rejects_table_without_seats_list(): void
    {
        $table = $this->tableObject('tbl_a', 2);
        unset($table['seats']);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$table])])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas.objects.0.seats']);
    }

    public function test_update_rejects_seat_count_outside_min_and_max(): void
    {
        $tooFew = $this->tableObject('tbl_a', 1);
        $tooMany = $this->tableObject('tbl_b', 13);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$tooFew, $tooMany])])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas.objects.0.seatCount', 'canvas.objects.1.seatCount']);
    }

    public function test_update_rejects_duplicate_seat_ids_across_tables(): void
    {
        $tableB = $this->tableObject('tbl_b', 2, ['seats' => ['seat_tbl_a_1', 'seat_tbl_b_2']]);
        $canvas = $this->canvasWith([$this->tableObject('tbl_a', 2), $tableB]);

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $canvas])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['canvas.objects.1.seats.0']);
    }

    public function test_update_does_not_change_canvas_when_validation_fails(): void
    {
        $original = $this->floorPlan->canvas_state;

        $this->withToken($this->token)
            ->putJson($this->updateUrl(), ['canvas' => $this->canvasWith([$this->tableObject('tbl_a', 4, ['seatCount' => 9])])])
            ->assertStatus(422);

        $this->assertSame($original, $this->floorPlan->refresh()->canvas_state);
    }

    public function test_update_returns_404_for_missing_floor_plan(): void
    {
        $response = $this->withToken($this->token)->putJson(
            '/api/staff/events/' . $this->event->id . '/floor-plans/' . Str::uuid(),
            ['canvas' => $this->defaultTestCanvas()],
        );

        $response->assertStatus(404);
    }

    public function test_update_returns_404_for_floor_plan_of_another_event(): void
    {
        $otherEvent = $this->createEvent();
        $otherPlan = $this->createFloorPlan($otherEvent);
        $original = $otherPlan->canvas_state;

        $this->withToken($this->token)
            ->putJson($this->updateUrl($this->event, $otherPlan), ['canvas' => $this->canvasWith([])])
            ->assertStatus(404);

        $this->assertSame($original, $otherPlan->refresh()->canvas_state);
    }

    public function test_update_returns_404_for_event_of_another_account(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);
        $otherPlan = $this->createFloorPlan($otherEvent);

        $this->withToken($this->token)
            ->putJson($this->updateUrl($otherEvent, $otherPlan), ['canvas' => $this->canvasWith([])])
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_update_floor_plan(): void
    {
        $this->putJson($this->updateUrl(), ['canvas' => $this->defaultTestCanvas()])->assertStatus(401);
    }

    public function test_client_cannot_update_floor_plan_through_staff_route(): void
    {
        $this->actingAs($this->client, 'client')
            ->putJson($this->updateUrl(), ['canvas' => $this->defaultTestCanvas()])
            ->assertStatus(401);
    }
}
