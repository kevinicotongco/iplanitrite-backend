<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    case Pending = 'Pending';
    case Ready = 'Ready';
    case PartiallyPaid = 'PartiallyPaid';
    case FullyPaid = 'FullyPaid';
    case Canceled = 'Canceled';
}
