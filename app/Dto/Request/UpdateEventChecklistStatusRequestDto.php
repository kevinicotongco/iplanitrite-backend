<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\EventChecklistStatusEnum;

readonly class UpdateEventChecklistStatusRequestDto
{
    public function __construct(
        public EventChecklistStatusEnum $status,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: EventChecklistStatusEnum::from($data['status']),
        );
    }
}
