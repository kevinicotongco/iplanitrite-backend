<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventPrice;

readonly class EventPriceResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public float $costPrice,
        public float $retailPrice,
        public int $sortOrder,
        public ?SupplierResponseDto $supplier,
    ) {}

    public static function fromModel(EventPrice $eventPrice): self
    {
        return new self(
            id: $eventPrice->id,
            name: $eventPrice->name,
            costPrice: (float) $eventPrice->cost_price,
            retailPrice: (float) $eventPrice->retail_price,
            sortOrder: $eventPrice->sort_order,
            supplier: $eventPrice->supplier
                ? SupplierResponseDto::fromModel($eventPrice->supplier)
                : null,
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
            'costPrice' => $this->costPrice,
            'retailPrice' => $this->retailPrice,
            'sortOrder' => $this->sortOrder,
            'supplier' => $this->supplier?->toArray(),
        ];
    }
}
