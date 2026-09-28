<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventChecklistGroupWithChecklistsData;
use App\Data\EventChecklistWithRelationsData;

readonly class EventChecklistGroupResponseDto
{
    /**
     * @param array<int, EventChecklistResponseDto> $checklists
     */
    public function __construct(
        public string $id,
        public string $eventId,
        public string $name,
        public string $eventType,
        public string $checklistType,
        public int $sortOrder,
        public array $checklists,
    ) {}

    public static function fromData(EventChecklistGroupWithChecklistsData $groupData): self
    {
        return new self(
            id: $groupData->id,
            eventId: $groupData->eventId,
            name: $groupData->name,
            eventType: $groupData->eventType->value,
            checklistType: $groupData->checklistType->value,
            sortOrder: $groupData->sortOrder,
            checklists: $groupData->checklists
                ->map(fn(EventChecklistWithRelationsData $checklistData): EventChecklistResponseDto => EventChecklistResponseDto::fromData($checklistData))
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
            'name' => $this->name,
            'eventType' => $this->eventType,
            'checklistType' => $this->checklistType,
            'sortOrder' => $this->sortOrder,
            'checklists' => array_map(
                fn(EventChecklistResponseDto $checklist): array => $checklist->toArray(),
                $this->checklists
            ),
        ];
    }
}
