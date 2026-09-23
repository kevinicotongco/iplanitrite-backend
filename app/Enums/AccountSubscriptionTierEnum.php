<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountSubscriptionTierEnum: string
{
    case Free = 'Free';
    case Standard = 'Standard';
    case Premium = 'Premium';
}
