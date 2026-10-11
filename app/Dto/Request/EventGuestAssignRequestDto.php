<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class EventGuestAssignRequestDto
{
    public function __construct(
        public string $seatId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            seatId: $data['seatId'],
        );
    }
}
