<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Celebrant;

readonly class CelebrantResponseDto
{
    public function __construct(
        public string $id,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?DocumentResponseDto $profilePicture,
        public ?AddressResponseDto $address,
        public ?ContactNumberResponseDto $contactNumber,
    ) {}

    public static function fromModel(Celebrant $celebrant): self
    {
        return new self(
            id: $celebrant->id,
            firstName: $celebrant->first_name,
            middleName: $celebrant->middle_name,
            lastName: $celebrant->last_name,
            profilePicture: $celebrant->profilePictureDocument
                ? DocumentResponseDto::fromModel($celebrant->profilePictureDocument)
                : null,
            address: $celebrant->address
                ? AddressResponseDto::fromModel($celebrant->address)
                : null,
            contactNumber: $celebrant->contactNumber
                ? ContactNumberResponseDto::fromModel($celebrant->contactNumber)
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'profilePicture' => $this->profilePicture?->toArray(),
            'address' => $this->address?->toArray(),
            'contactNumber' => $this->contactNumber?->toArray(),
        ];
    }
}
