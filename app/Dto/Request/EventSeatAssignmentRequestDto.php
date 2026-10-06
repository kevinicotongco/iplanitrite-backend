<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class EventSeatAssignmentRequestDto
{
    public function __construct(
        public string $eventGuestId,
        public ?string $seatId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventGuestId: $data['eventGuestId'],
            seatId: $data['seatId'] ?? null,
        );
    }
}
