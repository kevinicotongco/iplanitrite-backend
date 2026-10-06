<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\EventFloorPlanWithSeatsData;
use App\Models\Event;
use App\Models\EventFloorPlan;

readonly class EventFloorPlanService
{
    public function __construct(
        private EventFloorPlan $eventFloorPlanModel,
        private EventSeatService $eventSeatService,
    ) {}

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

    public function getFloorPlanForEvent(Event $event, string $floorPlanId): EventFloorPlan
    {
        return $this->eventFloorPlanModel::where('id', $floorPlanId)
            ->where('event_id', $event->id)
            ->firstOrFail();
    }
}
