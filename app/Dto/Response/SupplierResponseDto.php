<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Supplier;

readonly class SupplierResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public ?DocumentResponseDto $logo,
        public ?string $description,
        public ?AddressResponseDto $address,
        public CountryResponseDto $country,
        public ?ContactNumberResponseDto $contactNumber,
        public string $subscriptionTier,
        public string $timezone,
    ) {}

    public static function fromModel(Supplier $supplier): self
    {
        return new self(
            id: $supplier->id,
            name: $supplier->name,
            status: $supplier->status->value,
            logo: $supplier->logoDocument
                ? DocumentResponseDto::fromModel($supplier->logoDocument)
                : null,
            description: $supplier->description,
            address: $supplier->address
                ? AddressResponseDto::fromModel($supplier->address)
                : null,
            country: CountryResponseDto::fromModel($supplier->country),
            contactNumber: $supplier->contactNumber
                ? ContactNumberResponseDto::fromModel($supplier->contactNumber)
                : null,
            subscriptionTier: $supplier->subscription_tier->value,
            timezone: $supplier->timezone,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'logo' => $this->logo?->toArray(),
            'description' => $this->description,
            'address' => $this->address?->toArray(),
            'country' => $this->country->toArray(),
            'contactNumber' => $this->contactNumber?->toArray(),
            'subscriptionTier' => $this->subscriptionTier,
            'timezone' => $this->timezone,
        ];
    }
}
