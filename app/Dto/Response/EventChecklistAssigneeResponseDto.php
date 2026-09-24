<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Enums\EventChecklistAssigneeTypeEnum;
use App\Models\EventChecklistAssignee;

readonly class EventChecklistAssigneeResponseDto
{
    public function __construct(
        public string $id,
        public string $assigneeId,
        public EventChecklistAssigneeTypeEnum $assigneeType,
        public string $eventChecklistId,
    ) {}

    public static function fromModel(EventChecklistAssignee $assignee): self
    {
        return new self(
            id: $assignee->id,
            assigneeId: $assignee->assignee_id,
            assigneeType: $assignee->assignee_type,
            eventChecklistId: $assignee->event_checklist_id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assigneeId' => $this->assigneeId,
            'assigneeType' => $this->assigneeType->value,
            'eventChecklistId' => $this->eventChecklistId,
        ];
    }
}
