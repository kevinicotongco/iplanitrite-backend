<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\InvoicePaymentStatusEnum;

readonly class UpdateInvoicePaymentStatusRequestDto
{
    public function __construct(
        public InvoicePaymentStatusEnum $status,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: InvoicePaymentStatusEnum::from($data['status']),
        );
    }
}
