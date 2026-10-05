<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case Cash = 'Cash';
    case BankTransfer = 'BankTransfer';
}
