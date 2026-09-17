<?php

declare(strict_types=1);

namespace App\Enums;

enum EventGuestStatusEnum: string
{
    case Pending = 'Pending';
    case Requested = 'Requested';
    case Denied = 'Denied';
    case Approved = 'Approved';
}
