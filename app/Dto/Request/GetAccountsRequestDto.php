<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;

readonly class GetAccountsRequestDto
{
    public function __construct(
        public ?string $searchText,
        public ?AccountStatusEnum $status,
        public ?AccountSubscriptionTierEnum $tier,
        public ?string $countryId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            searchText: $data['searchText'] ?? null,
            status: isset($data['status']) ? AccountStatusEnum::from($data['status']) : null,
            tier: isset($data['tier']) ? AccountSubscriptionTierEnum::from($data['tier']) : null,
            countryId: $data['countryId'] ?? null,
        );
    }
}
