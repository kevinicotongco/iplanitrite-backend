<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Account;

readonly class AccountResponseDto
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

    public static function fromModel(Account $account): self
    {
        return new self(
            id: $account->id,
            name: $account->name,
            status: $account->status->value,
            logo: $account->logoDocument
                ? DocumentResponseDto::fromModel($account->logoDocument)
                : null,
            description: $account->description,
            address: $account->address
                ? AddressResponseDto::fromModel($account->address)
                : null,
            country: CountryResponseDto::fromModel($account->country),
            contactNumber: $account->contactNumber
                ? ContactNumberResponseDto::fromModel($account->contactNumber)
                : null,
            subscriptionTier: $account->subscription_tier->value,
            timezone: $account->timezone,
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
