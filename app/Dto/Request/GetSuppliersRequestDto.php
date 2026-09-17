<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;

readonly class GetSuppliersRequestDto
{
    public function __construct(
        public ?string $searchText,
        public ?SupplierStatusEnum $status,
        public ?SupplierSubscriptionTierEnum $tier,
        public ?string $countryId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            searchText: $data['searchText'] ?? null,
            status: isset($data['status']) ? SupplierStatusEnum::from($data['status']) : null,
            tier: isset($data['tier']) ? SupplierSubscriptionTierEnum::from($data['tier']) : null,
            countryId: $data['countryId'] ?? null,
        );
    }
}
