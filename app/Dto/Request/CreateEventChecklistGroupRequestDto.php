<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\ChecklistGroupTypeEnum;

readonly class CreateEventChecklistGroupRequestDto
{
    public function __construct(
        public string $name,
        public ChecklistGroupTypeEnum $checklistType,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            checklistType: ChecklistGroupTypeEnum::from($data['checklistType']),
        );
    }
}
