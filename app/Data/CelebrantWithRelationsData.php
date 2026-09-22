<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Celebrant;

final readonly class CelebrantWithRelationsData
{
    public function __construct(
        public string $id,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?AddressData $address,
        public ?ContactNumberData $contactNumber,
    ) {}

    public static function fromModel(Celebrant $celebrant): self
    {
        return new self(
            id: $celebrant->id,
            firstName: $celebrant->first_name,
            middleName: $celebrant->middle_name,
            lastName: $celebrant->last_name,
            address: $celebrant->address ? AddressData::fromModel($celebrant->address) : null,
            contactNumber: $celebrant->contactNumber ? ContactNumberData::fromModel($celebrant->contactNumber) : null,
        );
    }
}
