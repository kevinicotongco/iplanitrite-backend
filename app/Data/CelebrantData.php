<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Celebrant;

final readonly class CelebrantData
{
    public function __construct(
        public string $id,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?string $addressId,
        public ?string $contactNumberId,
    ) {}

    public static function fromModel(Celebrant $celebrant): self
    {
        return new self(
            id: $celebrant->id,
            firstName: $celebrant->first_name,
            middleName: $celebrant->middle_name,
            lastName: $celebrant->last_name,
            addressId: $celebrant->address_id,
            contactNumberId: $celebrant->contact_number_id,
        );
    }
}
