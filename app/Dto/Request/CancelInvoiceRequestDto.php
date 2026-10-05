<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class CancelInvoiceRequestDto
{
    public function __construct(
        public ?string $cancellationNotes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cancellationNotes: $data['cancellationNotes'] ?? null,
        );
    }
}
