<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class CelebrantRequestDto
{
    public function __construct(
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?AddressRequestDto $address,
        public ?string $contactNumber,
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
            address: isset($data['address']) ? AddressRequestDto::fromArray($data['address']) : null,
            contactNumber: $data['contactNumber'] ?? null,
        );
    }
}
