<?php

declare(strict_types=1);

namespace App\Enums;

enum EventChecklistStatusEnum: string
{
    case Pending = 'Pending';
    case Completed = 'Completed';
}
