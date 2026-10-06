<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventFloorPlanWithSeatsData;
use App\Models\EventSeat;

readonly class EventFloorPlanResponseDto
{
    /**
     * @param array<string, mixed> $canvas
     * @param list<EventSeatResponseDto> $seats
     */
    public function __construct(
        public string $id,
        public string $eventId,
        public array $canvas,
        public array $seats,
    ) {}

    public static function fromData(EventFloorPlanWithSeatsData $data): self
    {
        return new self(
            id: $data->floorPlan->id,
            eventId: $data->floorPlan->event_id,
            canvas: $data->floorPlan->canvas_state,
            seats: $data->seats
                ->map(fn(EventSeat $seat): EventSeatResponseDto => EventSeatResponseDto::fromModel($seat))
                ->values()
                ->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'eventId' => $this->eventId,
            'canvas' => $this->canvas,
            'seats' => array_map(
                fn(EventSeatResponseDto $seat): array => $seat->toArray(),
                $this->seats,
            ),
        ];
    }
}
