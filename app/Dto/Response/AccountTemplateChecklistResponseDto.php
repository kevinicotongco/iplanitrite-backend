<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\AccountTemplateChecklistData;
use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Enums\ResponsibilityTypeEnum;
use App\Models\AccountTemplateChecklist;

readonly class AccountTemplateChecklistResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?ChecklistFrequencyTypeEnum $frequencyType,
        public ?FrequencyAnchorEnum $frequencyAnchor,
        public ?int $frequencyValue,
        public ?ResponsibilityTypeEnum $responsibilityType,
        public ?SupplierResponseDto $supplier,
        public int $sortOrder,
    ) {}

    public static function fromModel(AccountTemplateChecklist $checklist): self
    {
        return new self(
            id: $checklist->id,
            name: $checklist->name,
            description: $checklist->description,
            frequencyType: $checklist->frequency_type,
            frequencyAnchor: $checklist->frequency_anchor,
            frequencyValue: $checklist->frequency_value,
            responsibilityType: $checklist->responsibility_type,
            supplier: $checklist->supplier ? SupplierResponseDto::fromModel($checklist->supplier) : null,
            sortOrder: $checklist->sort_order,
        );
    }

    public static function fromData(AccountTemplateChecklistData $data, ?SupplierResponseDto $supplier = null): self
    {
        return new self(
            id: $data->id,
            name: $data->name,
            description: $data->description,
            frequencyType: $data->frequencyType,
            frequencyAnchor: $data->frequencyAnchor,
            frequencyValue: $data->frequencyValue,
            responsibilityType: $data->responsibilityType,
            supplier: $supplier,
            sortOrder: $data->sortOrder,
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
            'description' => $this->description,
            'frequencyType' => $this->frequencyType?->value,
            'frequencyAnchor' => $this->frequencyAnchor?->value,
            'frequencyValue' => $this->frequencyValue,
            'responsibilityType' => $this->responsibilityType?->value,
            'supplier' => $this->supplier?->toArray(),
            'sortOrder' => $this->sortOrder,
        ];
    }
}
