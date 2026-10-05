<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class CreateInvoiceItemRequestDto
{
    public function __construct(
        public string $description,
        public string $amount,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'],
            amount: (string) $data['amount'],
        );
    }
}
