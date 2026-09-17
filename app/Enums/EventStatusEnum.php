<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatusEnum: string
{
    case Pending = 'Pending';
    case Ongoing = 'Ongoing';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
