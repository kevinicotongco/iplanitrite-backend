<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\InvoiceItem;

readonly class InvoiceItemResponseDto
{
    public function __construct(
        public string $id,
        public string $description,
        public string $amount,
    ) {}

    public static function fromModel(InvoiceItem $item): self
    {
        return new self(
            id: $item->id,
            description: $item->description,
            amount: $item->amount,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount' => $this->amount,
        ];
    }
}
