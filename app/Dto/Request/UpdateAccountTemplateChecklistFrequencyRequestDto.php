<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;

readonly class UpdateAccountTemplateChecklistFrequencyRequestDto
{
    public function __construct(
        public ChecklistFrequencyTypeEnum $frequencyType,
        public FrequencyAnchorEnum $frequencyAnchor,
        public int $frequencyValue,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            frequencyType: ChecklistFrequencyTypeEnum::from($data['frequencyType']),
            frequencyAnchor: FrequencyAnchorEnum::from($data['frequencyAnchor']),
            frequencyValue: $data['frequencyValue'],
        );
    }
}
