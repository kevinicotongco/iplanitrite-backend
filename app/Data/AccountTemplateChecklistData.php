<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Models\AccountTemplateChecklist;

final readonly class AccountTemplateChecklistData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public int $frequencyDays,
        public ChecklistFrequencyTypeEnum $frequencyType,
        public FrequencyAnchorEnum $frequencyAnchor,
    ) {}

    public static function fromModel(AccountTemplateChecklist $checklist): self
    {
        return new self(
            id: $checklist->id,
            name: $checklist->name,
            description: $checklist->description,
            frequencyDays: $checklist->frequency_days,
            frequencyType: $checklist->frequency_type,
            frequencyAnchor: $checklist->frequency_anchor,
        );
    }
}
