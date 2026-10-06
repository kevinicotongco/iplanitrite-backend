<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventSeat;

readonly class EventSeatResponseDto
{
    public function __construct(
        public string $tableId,
        public string $seatId,
        public string $eventGuestId,
    ) {}

    public static function fromModel(EventSeat $eventSeat): self
    {
        return new self(
            tableId: $eventSeat->table_id,
            seatId: $eventSeat->seat_id,
            eventGuestId: $eventSeat->event_guest_id,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'tableId' => $this->tableId,
            'seatId' => $this->seatId,
            'eventGuestId' => $this->eventGuestId,
        ];
    }
}
