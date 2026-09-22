<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Data\SupplierTemplateChecklistGroupWithChecklistsData;
use App\Enums\EventTypeEnum;
use App\Models\SupplierTemplateChecklistGroup;
use Illuminate\Support\Collection;

readonly class SupplierTemplateChecklistGroupService
{
    public function __construct(
        private SupplierTemplateChecklistGroup $model,
    ) {}

    /**
     * Get template checklist groups with their checklists for a specific supplier and event type
     *
     * @param string $supplierId
     * @param EventTypeEnum $eventType
     * @return Collection<SupplierTemplateChecklistGroupWithChecklistsData>
     */
    public function getTemplateGroupsWithChecklists(string $supplierId, EventTypeEnum $eventType): Collection
    {
        $templateGroups = $this->model::where('supplier_id', $supplierId)
            ->where('event_type', $eventType->value)
            ->with('checklists')
            ->get();

        return $templateGroups->map(function ($group) {
            return SupplierTemplateChecklistGroupWithChecklistsData::fromModel($group);
        });
    }
}
