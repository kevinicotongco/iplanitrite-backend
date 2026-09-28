<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventChecklistAssigneeData;

readonly class EventChecklistUpdateAssigneeResponseDto
{
    public function __construct(
        public string $assigneeId,
        public string $assigneeType,
        public string $assigneeName,
        public ?DocumentResponseDto $assigneeProfilePicture,
    ) {}

    public static function fromData(EventChecklistAssigneeData $assigneeData): self
    {
        return new self(
            assigneeId: $assigneeData->assigneeId,
            assigneeType: $assigneeData->assigneeType->value,
            assigneeName: $assigneeData->assigneeName,
            assigneeProfilePicture: $assigneeData->assigneeProfilePicture
                ? DocumentResponseDto::fromModel($assigneeData->assigneeProfilePicture)
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'assigneeId' => $this->assigneeId,
            'assigneeType' => $this->assigneeType,
            'assigneeName' => $this->assigneeName,
            'assigneeProfilePicture' => $this->assigneeProfilePicture?->toArray(),
        ];
    }
}
