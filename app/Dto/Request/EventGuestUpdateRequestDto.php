<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class EventGuestUpdateRequestDto
{
    public function __construct(
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public string $eventGuestGroupId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['firstName'],
            middleName: $data['middleName'] ?? null,
            lastName: $data['lastName'],
            eventGuestGroupId: $data['eventGuestGroupId'],
        );
    }
}
