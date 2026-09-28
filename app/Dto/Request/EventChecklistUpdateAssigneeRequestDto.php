<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\EventChecklistAssigneeTypeEnum;

readonly class EventChecklistUpdateAssigneeRequestDto
{
    public function __construct(
        public string $assigneeId,
        public EventChecklistAssigneeTypeEnum $assigneeType,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            assigneeId: $data['assigneeId'],
            assigneeType: EventChecklistAssigneeTypeEnum::from($data['assigneeType']),
        );
    }
}
