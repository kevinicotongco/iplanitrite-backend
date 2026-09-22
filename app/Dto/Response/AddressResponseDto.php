<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\AddressData;
use App\Models\Address;
use App\Models\Country;

readonly class AddressResponseDto
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
        public CountryResponseDto $country,
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
            country: CountryResponseDto::fromModel($address->country),
        );
    }

    public static function fromAddressData(AddressData $addressData): self
    {
        $country = Country::findOrFail($addressData->countryId);
        
        return new self(
            id: $addressData->id,
            line1: $addressData->line1,
            line2: $addressData->line2,
            city: $addressData->city,
            state: $addressData->state,
            zip: $addressData->zip,
            lat: $addressData->lat,
            long: $addressData->long,
            country: CountryResponseDto::fromModel($country),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'lat' => $this->lat,
            'long' => $this->long,
            'country' => $this->country->toArray(),
        ];
    }
}
