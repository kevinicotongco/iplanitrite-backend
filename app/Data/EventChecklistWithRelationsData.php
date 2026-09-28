<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\EventChecklistStatusEnum;
use App\Models\EventChecklist;
use App\Models\EventChecklistAssignee;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class EventChecklistWithRelationsData
{
    /**
     * @param Collection<int, EventChecklistAssigneeData> $assignees
     */
    public function __construct(
        public string $id,
        public string $eventChecklistGroupId,
        public string $name,
        public ?string $description,
        public EventChecklistStatusEnum $status,
        public ?Carbon $dueDate,
        public int $sortOrder,
        public ?Supplier $supplier,
        public Collection $assignees,
    ) {}

    public static function fromModel(EventChecklist $checklist): self
    {
        $assignees = $checklist->assignees
            ->map(fn(EventChecklistAssignee $assignee): ?EventChecklistAssigneeData => EventChecklistAssigneeData::fromModel($assignee))
            ->filter()
            ->values();

        return new self(
            id: $checklist->id,
            eventChecklistGroupId: $checklist->event_checklist_group_id,
            name: $checklist->name,
            description: $checklist->description,
            status: $checklist->status,
            dueDate: $checklist->due_date,
            sortOrder: $checklist->sort_order,
            supplier: $checklist->supplier,
            assignees: $assignees,
        );
    }
}
