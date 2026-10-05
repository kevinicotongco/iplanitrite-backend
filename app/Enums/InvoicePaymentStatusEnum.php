<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoicePaymentStatusEnum: string
{
    case ForReview = 'ForReview';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
}
