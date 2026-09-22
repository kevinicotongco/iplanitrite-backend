<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\EventTypeEnum;
use App\Models\SupplierTemplateChecklistGroup;
use Illuminate\Support\Collection;

final readonly class SupplierTemplateChecklistGroupWithChecklistsData
{
    /**
     * @param Collection<SupplierTemplateChecklistData> $checklists
     */
    public function __construct(
        public string $id,
        public string $name,
        public EventTypeEnum $eventType,
        public Collection $checklists,
    ) {}

    public static function fromModel(SupplierTemplateChecklistGroup $group): self
    {
        $checklists = $group->checklists->map(function ($checklist) {
            return SupplierTemplateChecklistData::fromModel($checklist);
        });

        return new self(
            id: $group->id,
            name: $group->name,
            eventType: $group->event_type,
            checklists: $checklists,
        );
    }
}
