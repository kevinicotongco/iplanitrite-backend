<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class CreateSupplierStaffRequestDto
{
    public function __construct(
        public string $email,
        public string $firstName,
        public ?string $middleName,
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
            middleName: $data['middleName'] ?? null,
            lastName: $data['lastName'],
        );
    }
}
