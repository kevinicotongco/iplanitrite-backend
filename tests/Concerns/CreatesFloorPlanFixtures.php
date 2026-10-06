<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\EventTypeEnum;
use App\Enums\FloorPlanObjectCategoryEnum;
use App\Enums\FloorPlanObjectShapeEnum;
use App\Enums\FloorPlanObjectTypeEnum;
use App\Models\Event;
use App\Models\EventFloorPlan;
use App\Models\EventGuest;
use App\Models\EventGuestGroup;
use App\Models\EventSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesFloorPlanFixtures
{
    use CreatesEventPricingFixtures;

    protected const TABLE_ONE = 'tbl_1';
    protected const TABLE_TWO = 'tbl_2';
    protected const STAGE = 'stage_1';

    /**
     * @return list<string>
     */
    protected function seatIds(string $tableId, int $count): array
    {
        return array_map(fn(int $number): string => 'seat_' . $tableId . '_' . $number, range(1, $count));
    }

    /**
     * @return array<string, mixed>
     */
    protected function tableObject(string $id, int $seatCount, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'type' => FloorPlanObjectTypeEnum::RoundTable->value,
            'label' => 'Table ' . $id,
            'subtitle' => 'VIP / Family',
            'x' => 350,
            'y' => 240,
            'radius' => 60,
            'rotation' => 0,
            'scaleX' => 1,
            'scaleY' => 1,
            'zIndex' => 2,
            'fill' => '#ffffff',
            'stroke' => '#cbd5e1',
            'strokeWidth' => 2,
            'shadowColor' => 'rgba(0,0,0,0.1)',
            'shadowBlur' => 10,
            'shadowOffsetX' => 0,
            'shadowOffsetY' => 4,
            'seatCount' => $seatCount,
            'seatRadius' => 12,
            'seatSpacing' => 18,
            'seats' => $this->seatIds($id, $seatCount),
            'category' => FloorPlanObjectCategoryEnum::Table->value,
            'shape' => FloorPlanObjectShapeEnum::Circle->value,
            'isLocked' => false,
            'isVisible' => true,
            'isSelectable' => true,
            'minSeats' => 2,
            'maxSeats' => 12,
            'notes' => 'Near the dance floor, needs 1 high chair',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    protected function stageObject(): array
    {
        return [
            'id' => self::STAGE,
            'type' => FloorPlanObjectTypeEnum::Stage->value,
            'label' => 'Main Stage / DJ',
            'subtitle' => 'Band & Audio Setup',
            'x' => 500,
            'y' => 50,
            'width' => 200,
            'height' => 80,
            'rotation' => 0,
            'scaleX' => 1,
            'scaleY' => 1,
            'zIndex' => 1,
            'fill' => '#334155',
            'stroke' => '#1e293b',
            'strokeWidth' => 2,
            'cornerRadius' => 6,
            'category' => FloorPlanObjectCategoryEnum::Fixture->value,
            'shape' => FloorPlanObjectShapeEnum::Rectangle->value,
            'isLocked' => true,
            'isVisible' => true,
            'isSelectable' => false,
            'notes' => 'Power outlet located on left side',
        ];
    }

    /**
     * @param list<array<string, mixed>> $objects
     * @return array<string, mixed>
     */
    protected function canvasWith(array $objects): array
    {
        return [
            'version' => '1.0',
            'canvas' => [
                'width' => 1200,
                'height' => 800,
                'zoom' => 1,
                'gridSize' => 20,
                'snapToGrid' => true,
                'backgroundColor' => '#f8fafc',
            ],
            'objects' => $objects,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultTestCanvas(): array
    {
        return $this->canvasWith([
            $this->tableObject(self::TABLE_ONE, 4),
            $this->tableObject(self::TABLE_TWO, 2),
            $this->stageObject(),
        ]);
    }

    /**
     * @param array<string, mixed>|null $canvas
     */
    protected function createFloorPlan(Event $event, ?array $canvas = null): EventFloorPlan
    {
        return EventFloorPlan::factory()->create([
            'event_id' => $event->id,
            'canvas_state' => $canvas ?? $this->defaultTestCanvas(),
        ]);
    }

    protected function createGuest(Event $event, string $firstName = 'Guest'): EventGuest
    {
        $group = EventGuestGroup::firstOrCreate(
            ['event_id' => $event->id, 'name' => 'Family'],
            ['event_type' => EventTypeEnum::Birthday->value],
        );

        return EventGuest::create([
            'event_guest_group_id' => $group->id,
            'first_name' => $firstName,
            'last_name' => 'Tester',
        ]);
    }

    protected function seatGuest(EventFloorPlan $floorPlan, string $tableId, string $seatId, EventGuest $guest): EventSeat
    {
        return EventSeat::create([
            'event_floor_plan_id' => $floorPlan->id,
            'table_id' => $tableId,
            'seat_id' => $seatId,
            'event_guest_id' => $guest->id,
            'created_by' => $this->client->id,
            'updated_by' => $this->client->id,
        ]);
    }

    protected function attachClientToEvent(Event $event): void
    {
        DB::table('event_clients')->insert([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'client_id' => $this->client->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
