<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class CreateSupplierRequestDto
{
    public function __construct(
        public string $companyName,
        public string $contactPerson,
        public string $contactNumber,
        public AddressRequestDto $address,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            companyName: $data['companyName'],
            contactPerson: $data['contactPerson'],
            contactNumber: $data['contactNumber'],
            address: AddressRequestDto::fromArray($data['address']),
        );
    }
}
