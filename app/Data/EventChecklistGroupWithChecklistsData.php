<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventTypeEnum;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use Illuminate\Support\Collection;

final readonly class EventChecklistGroupWithChecklistsData
{
    /**
     * @param Collection<int, EventChecklistWithRelationsData> $checklists
     */
    public function __construct(
        public string $id,
        public string $eventId,
        public string $name,
        public EventTypeEnum $eventType,
        public ChecklistGroupTypeEnum $checklistType,
        public int $sortOrder,
        public Collection $checklists,
    ) {}

    public static function fromModel(EventChecklistGroup $group): self
    {
        return new self(
            id: $group->id,
            eventId: $group->event_id,
            name: $group->name,
            eventType: $group->event_type,
            checklistType: $group->checklist_type,
            sortOrder: $group->sort_order,
            checklists: $group->checklists->map(
                fn(EventChecklist $checklist): EventChecklistWithRelationsData => EventChecklistWithRelationsData::fromModel($checklist)
            ),
        );
    }
}
