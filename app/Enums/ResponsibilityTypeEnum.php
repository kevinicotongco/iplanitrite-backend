<?php

declare(strict_types=1);

namespace App\Enums;

enum ResponsibilityTypeEnum: string
{
    case Staff = 'Staff';
    case Client = 'Client';
    case Both = 'Both';
}
