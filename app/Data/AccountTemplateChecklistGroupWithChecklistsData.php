<?php

declare(strict_types=1);

namespace App\Data;

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
        public Collection $checklists,
    ) {}

    public static function fromModel(AccountTemplateChecklistGroup $group): self
    {
        $checklists = $group->checklists->map(function ($checklist) {
            return AccountTemplateChecklistData::fromModel($checklist);
        });

        return new self(
            id: $group->id,
            name: $group->name,
            eventType: $group->event_type,
            checklists: $checklists,
        );
    }
}
