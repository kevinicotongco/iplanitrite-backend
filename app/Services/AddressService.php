<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AddressData;
use App\Dto\Request\AddressRequestDto;
use App\Models\Address;

class AddressService
{
    public function __construct(
        private Address $addressModel,
    ) {}

    /**
     * Create an address
     *
     * @param AddressRequestDto $dto
     * @param string $countryId
     * @return AddressData
     */
    public function createAddress(AddressRequestDto $dto, string $countryId): AddressData
    {
        $address = $this->addressModel::create([
            'line1' => $dto->line1,
            'line2' => $dto->line2,
            'city' => $dto->city,
            'state' => $dto->state,
            'zip' => $dto->zip,
            'lat' => $dto->lat,
            'long' => $dto->long,
            'country_id' => $countryId,
        ]);

        return AddressData::fromModel($address);
    }

    /**
     * Update an address
     *
     * @param Address $address
     * @param AddressRequestDto $dto
     * @param string $countryId
     * @return void
     */
    public function updateAddress(Address $address, AddressRequestDto $dto, string $countryId): void
    {
        $address->update([
            'line1' => $dto->line1,
            'line2' => $dto->line2,
            'city' => $dto->city,
            'state' => $dto->state,
            'zip' => $dto->zip,
            'lat' => $dto->lat,
            'long' => $dto->long,
            'country_id' => $countryId,
        ]);
    }
}
