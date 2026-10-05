<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\AccountSubscriptionTierEnum;

readonly class CreateAccountRequestDto
{
    public function __construct(
        public string $name,
        public ?string $description,
        public AccountSubscriptionTierEnum $subscriptionTier,
        public string $countryId,
        public string $timezone,
        public ?AddressRequestDto $address,
        public ?string $contactNumber,
        public CreateStaffRequestDto $staff,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            subscriptionTier: AccountSubscriptionTierEnum::from($data['subscriptionTier']),
            countryId: $data['countryId'],
            timezone: $data['timezone'],
            address: isset($data['address']) ? AddressRequestDto::fromArray($data['address']) : null,
            contactNumber: $data['contactNumber'] ?? null,
            staff: CreateStaffRequestDto::fromArray($data['staff']),
        );
    }
}
