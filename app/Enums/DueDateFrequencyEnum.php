<?php

declare(strict_types=1);

namespace App\Enums;

enum DueDateFrequencyEnum: string
{
    case Days = 'Days';
    case Weeks = 'Weeks';
    case Months = 'Months';
}
