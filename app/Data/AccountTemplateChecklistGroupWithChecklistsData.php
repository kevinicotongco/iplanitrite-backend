<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventTypeEnum;
use App\Models\AccountTemplateChecklistGroup;
use Illuminate\Support\Collection;

final readonly class AccountTemplateChecklistGroupWithChecklistsData
{
    /**
     * @param Collection<AccountTemplateChecklistData> $checklists
     */
    public function __construct(
        public string $id,
        public string $name,
        public EventTypeEnum $eventType,
        public ChecklistGroupTypeEnum $checklistType,
        public int $sortOrder,
        public Collection $checklists,
    ) {}

    public static function fromModel(AccountTemplateChecklistGroup $group): self
    {
        $checklists = $group->checklists()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($checklist) {
                return AccountTemplateChecklistData::fromModel($checklist);
            });

        return new self(
            id: $group->id,
            name: $group->name,
            eventType: $group->event_type,
            checklistType: $group->checklist_type,
            sortOrder: $group->sort_order,
            checklists: $checklists,
        );
    }
}
