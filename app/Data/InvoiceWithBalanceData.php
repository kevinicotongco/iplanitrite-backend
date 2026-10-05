<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Invoice;

readonly class InvoiceWithBalanceData
{
    public function __construct(
        public Invoice $invoice,
        public string $balanceDue,
    ) {}
}
