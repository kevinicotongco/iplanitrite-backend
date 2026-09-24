<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Enums\ResponsibilityTypeEnum;
use App\Models\AccountTemplateChecklist;

final readonly class AccountTemplateChecklistData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?int $frequencyValue,
        public ?ChecklistFrequencyTypeEnum $frequencyType,
        public ?FrequencyAnchorEnum $frequencyAnchor,
        public ?ResponsibilityTypeEnum $responsibilityType,
        public ?string $supplierId,
        public int $sortOrder,
    ) {}

    public static function fromModel(AccountTemplateChecklist $checklist): self
    {
        return new self(
            id: $checklist->id,
            name: $checklist->name,
            description: $checklist->description,
            frequencyValue: $checklist->frequency_value,
            frequencyType: $checklist->frequency_type,
            frequencyAnchor: $checklist->frequency_anchor,
            responsibilityType: $checklist->responsibility_type,
            supplierId: $checklist->supplier_id,
            sortOrder: $checklist->sort_order,
        );
    }
}
