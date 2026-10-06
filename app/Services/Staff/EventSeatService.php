<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\FloorPlanCanvasData;
use App\Models\EventFloorPlan;
use App\Models\EventSeat;
use Illuminate\Support\Collection;

readonly class EventSeatService
{
    public function __construct(
        private EventSeat $eventSeatModel,
    ) {}

    /**
     * @return Collection<int, EventSeat>
     */
    public function getSeatsForFloorPlan(EventFloorPlan $floorPlan): Collection
    {
        return $this->eventSeatModel::where('event_floor_plan_id', $floorPlan->id)
            ->orderBy('table_id')
            ->orderBy('seat_id')
            ->get();
    }

    public function deleteSeatsOutsideCanvas(EventFloorPlan $floorPlan, FloorPlanCanvasData $canvas): void
    {
        $this->getSeatsForFloorPlan($floorPlan)
            ->filter(fn(EventSeat $seat): bool => !$canvas->hasSeat($seat->table_id, $seat->seat_id))
            ->each(fn(EventSeat $seat) => $seat->delete());
    }
}
