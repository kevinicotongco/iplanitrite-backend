<?php

declare(strict_types=1);

namespace App\Enums;

enum SupplierStatusEnum: string
{
    case Active = 'Active';
    case Disabled = 'Disabled';
    case FailedPayment = 'FailedPayment';
}
