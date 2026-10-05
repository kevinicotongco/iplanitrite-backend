<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class UpdateInvoiceDueDateRequestDto
{
    public function __construct(
        public string $dueDate,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dueDate: $data['dueDate'],
        );
    }
}
