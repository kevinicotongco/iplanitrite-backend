<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventChecklistAssigneeData;
use App\Data\EventChecklistWithRelationsData;

readonly class EventChecklistResponseDto
{
    /**
     * @param array<int, EventChecklistUpdateAssigneeResponseDto> $assignees
     */
    public function __construct(
        public string $id,
        public string $eventChecklistGroupId,
        public string $name,
        public ?string $description,
        public string $status,
        public ?string $dueDate,
        public int $sortOrder,
        public ?SupplierResponseDto $supplier,
        public array $assignees,
    ) {}

    public static function fromData(EventChecklistWithRelationsData $checklistData): self
    {
        return new self(
            id: $checklistData->id,
            eventChecklistGroupId: $checklistData->eventChecklistGroupId,
            name: $checklistData->name,
            description: $checklistData->description,
            status: $checklistData->status->value,
            dueDate: $checklistData->dueDate?->format('Y-m-d'),
            sortOrder: $checklistData->sortOrder,
            supplier: $checklistData->supplier ? SupplierResponseDto::fromModel($checklistData->supplier) : null,
            assignees: $checklistData->assignees
                ->map(fn(EventChecklistAssigneeData $assigneeData): EventChecklistUpdateAssigneeResponseDto => EventChecklistUpdateAssigneeResponseDto::fromData($assigneeData))
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
            'eventChecklistGroupId' => $this->eventChecklistGroupId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'dueDate' => $this->dueDate,
            'sortOrder' => $this->sortOrder,
            'supplier' => $this->supplier?->toArray(),
            'assignees' => array_map(
                fn(EventChecklistUpdateAssigneeResponseDto $assignee): array => $assignee->toArray(),
                $this->assignees
            ),
        ];
    }
}
