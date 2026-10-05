<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\AccountBankDetail;
use Illuminate\Support\Collection;

readonly class InvoiceShowData
{
    /**
     * @param Collection<int, AccountBankDetail> $bankDetails
     */
    public function __construct(
        public InvoiceWithBalanceData $invoice,
        public Collection $bankDetails,
    ) {}
}
