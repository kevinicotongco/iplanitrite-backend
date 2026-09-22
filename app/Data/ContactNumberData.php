<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ContactNumber;

final readonly class ContactNumberData
{
    public function __construct(
        public string $id,
        public string $number,
        public string $countryId,
    ) {}

    public static function fromModel(ContactNumber $contactNumber): self
    {
        return new self(
            id: $contactNumber->id,
            number: $contactNumber->number,
            countryId: $contactNumber->country_id,
        );
    }
}
