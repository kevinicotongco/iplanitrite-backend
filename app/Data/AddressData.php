<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Address;

final readonly class AddressData
{
    public function __construct(
        public string $id,
        public string $line1,
        public ?string $line2,
        public string $city,
        public string $state,
        public string $zip,
        public ?string $lat,
        public ?string $long,
        public string $countryId,
    ) {}

    public static function fromModel(Address $address): self
    {
        return new self(
            id: $address->id,
            line1: $address->line1,
            line2: $address->line2,
            city: $address->city,
            state: $address->state,
            zip: $address->zip,
            lat: $address->lat,
            long: $address->long,
            countryId: $address->country_id,
        );
    }
}
