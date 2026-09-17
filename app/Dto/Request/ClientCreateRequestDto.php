<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class ClientCreateRequestDto
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            firstName: $data['firstName'],
            lastName: $data['lastName'],
        );
    }
}
