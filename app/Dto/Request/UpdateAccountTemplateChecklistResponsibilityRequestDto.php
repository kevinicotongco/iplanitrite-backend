<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\ResponsibilityTypeEnum;

readonly class UpdateAccountTemplateChecklistResponsibilityRequestDto
{
    public function __construct(
        public ResponsibilityTypeEnum $responsibilityType,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            responsibilityType: ResponsibilityTypeEnum::from($data['responsibilityType']),
        );
    }
}
