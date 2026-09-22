<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\CelebrantWithRelationsData;
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

    public static function fromCelebrantData(CelebrantWithRelationsData $celebrantData): self
    {
        return new self(
            id: $celebrantData->id,
            firstName: $celebrantData->firstName,
            middleName: $celebrantData->middleName,
            lastName: $celebrantData->lastName,
            profilePicture: null, // Profile picture not loaded in data class
            address: $celebrantData->address
                ? AddressResponseDto::fromAddressData($celebrantData->address)
                : null,
            contactNumber: $celebrantData->contactNumber
                ? ContactNumberResponseDto::fromContactNumberData($celebrantData->contactNumber)
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
