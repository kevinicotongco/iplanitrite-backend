<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Data\EventFloorPlanWithSeatsData;
use App\Data\FloorPlanCanvasData;
use App\Models\Event;
use App\Models\EventFloorPlan;

readonly class EventFloorPlanService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private EventFloorPlan $eventFloorPlanModel,
        private EventSeatService $eventSeatService,
    ) {}

    public function createDefaultFloorPlan(Event $event): EventFloorPlan
    {
        return $this->eventFloorPlanModel::create([
            'event_id' => $event->id,
            'canvas_state' => FloorPlanCanvasData::default()->toArray(),
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    public function getFloorPlanWithSeats(Event $event): EventFloorPlanWithSeatsData
    {
        $floorPlan = $this->eventFloorPlanModel::where('event_id', $event->id)
            ->orderBy('created_at')
            ->firstOrFail();

        return new EventFloorPlanWithSeatsData(
            floorPlan: $floorPlan,
            seats: $this->eventSeatService->getSeatsForFloorPlan($floorPlan),
        );
    }

    public function updateCanvas(Event $event, string $floorPlanId, FloorPlanCanvasData $canvas): void
    {
        $floorPlan = $this->eventFloorPlanModel::where('id', $floorPlanId)
            ->where('event_id', $event->id)
            ->firstOrFail();

        $floorPlan->update([
            'canvas_state' => $canvas->toArray(),
            'updated_by' => $this->authenticatedUser->id,
        ]);

        $this->eventSeatService->deleteSeatsOutsideCanvas($floorPlan, $canvas);
    }
}
