<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class EventGuestCancelStatusRequestDto
{
    public function __construct(
        public string $statusReason,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            statusReason: $data['statusReason'],
        );
    }
}
