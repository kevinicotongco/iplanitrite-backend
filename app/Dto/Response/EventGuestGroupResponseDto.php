<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventGuestGroup;

readonly class EventGuestGroupResponseDto
{
    public function __construct(
        public string $id,
        public string $eventId,
        public string $name,
    ) {}

    public static function fromModel(EventGuestGroup $eventGuestGroup): self
    {
        return new self(
            id: $eventGuestGroup->id,
            eventId: $eventGuestGroup->event_id,
            name: $eventGuestGroup->name,
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
            'name' => $this->name,
        ];
    }
}
