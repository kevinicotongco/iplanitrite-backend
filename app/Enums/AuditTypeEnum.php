<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditTypeEnum: string
{
    case Staff = 'Staff';
    case Client = 'Client';
}
