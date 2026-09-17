<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventChecklist;

readonly class EventChecklistResponseDto
{
    public function __construct(
        public string $id,
        public string $eventChecklistGroupId,
        public string $name,
        public ?string $description,
        public string $status,
    ) {}

    public static function fromModel(EventChecklist $eventChecklist): self
    {
        return new self(
            id: $eventChecklist->id,
            eventChecklistGroupId: $eventChecklist->event_checklist_group_id,
            name: $eventChecklist->name,
            description: $eventChecklist->description,
            status: $eventChecklist->status->value,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'eventChecklistGroupId' => $this->eventChecklistGroupId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
