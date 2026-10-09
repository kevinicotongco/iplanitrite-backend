<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\EventChecklistGroupsByTypeData;
use App\Data\EventChecklistGroupWithChecklistsData;
use App\Enums\ChecklistGroupTypeEnum;
use App\Models\Event;
use App\Models\EventChecklistGroup;
use Illuminate\Database\Eloquent\Relations\HasMany;

readonly class EventChecklistGroupService
{
    public function __construct(
        private EventChecklistGroup $eventChecklistGroupModel,
    ) {}

    public function getGroupsByType(Event $event): EventChecklistGroupsByTypeData
    {
        $groups = $this->eventChecklistGroupModel::where('event_id', $event->id)
            ->orderBy('sort_order')
            ->with([
                'checklists' => fn(HasMany $query): HasMany => $query->orderBy('sort_order'),
                'checklists.supplier.contactNumber',
                'checklists.supplier.address.country',
                'checklists.assignees.staff',
                'checklists.assignees.client',
            ])
            ->get()
            ->map(fn(EventChecklistGroup $group): EventChecklistGroupWithChecklistsData => EventChecklistGroupWithChecklistsData::fromModel($group));

        return new EventChecklistGroupsByTypeData(
            supplier: $groups->filter(
                fn(EventChecklistGroupWithChecklistsData $group): bool => $group->checklistType === ChecklistGroupTypeEnum::Supplier
            )->values(),
            general: $groups->filter(
                fn(EventChecklistGroupWithChecklistsData $group): bool => $group->checklistType === ChecklistGroupTypeEnum::General
            )->values(),
        );
    }
}
