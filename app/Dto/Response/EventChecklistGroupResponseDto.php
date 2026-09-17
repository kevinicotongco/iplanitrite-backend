<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventChecklistGroup;

readonly class EventChecklistGroupResponseDto
{
    public function __construct(
        public string $id,
        public string $eventId,
        public string $name,
    ) {}

    public static function fromModel(EventChecklistGroup $eventChecklistGroup): self
    {
        return new self(
            id: $eventChecklistGroup->id,
            eventId: $eventChecklistGroup->event_id,
            name: $eventChecklistGroup->name,
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
