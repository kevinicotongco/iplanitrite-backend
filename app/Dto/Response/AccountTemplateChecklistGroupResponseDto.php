<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\AccountTemplateChecklistGroupWithChecklistsData;
use App\Enums\ChecklistGroupTypeEnum;
use App\Models\AccountTemplateChecklistGroup;
use App\Models\Supplier;

readonly class AccountTemplateChecklistGroupResponseDto
{
    /**
     * @param AccountTemplateChecklistResponseDto[] $checklists
     */
    public function __construct(
        public string $id,
        public string $name,
        public ChecklistGroupTypeEnum $checklistType,
        public int $sortOrder,
        public array $checklists,
    ) {}

    public static function fromModel(AccountTemplateChecklistGroup $group): self
    {
        $checklists = $group->checklists()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($checklist) => AccountTemplateChecklistResponseDto::fromModel($checklist))
            ->toArray();

        return new self(
            id: $group->id,
            name: $group->name,
            checklistType: $group->checklist_type,
            sortOrder: $group->sort_order,
            checklists: $checklists,
        );
    }

    public static function fromData(AccountTemplateChecklistGroupWithChecklistsData $data): self
    {
        // Need to load suppliers for checklists
        $checklists = $data->checklists->map(function ($checklistData) {
            $supplier = null;
            if ($checklistData->supplierId) {
                $supplierModel = Supplier::find($checklistData->supplierId);
                if ($supplierModel) {
                    $supplier = SupplierResponseDto::fromModel($supplierModel);
                }
            }
            return AccountTemplateChecklistResponseDto::fromData($checklistData, $supplier);
        })->toArray();

        return new self(
            id: $data->id,
            name: $data->name,
            checklistType: $data->checklistType,
            sortOrder: $data->sortOrder,
            checklists: $checklists,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'checklistType' => $this->checklistType->value,
            'sortOrder' => $this->sortOrder,
            'checklists' => array_map(fn($checklist) => $checklist->toArray(), $this->checklists),
        ];
    }
}
