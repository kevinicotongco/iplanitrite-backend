<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class EventPriceRequestDto
{
    public function __construct(
        public string $name,
        public float $costPrice,
        public float $retailPrice,
        public ?string $supplierId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            costPrice: (float) $data['costPrice'],
            retailPrice: (float) $data['retailPrice'],
            supplierId: $data['supplierId'] ?? null,
        );
    }
}
