<?php

declare(strict_types=1);

namespace App\Enums;

enum EventChecklistAssigneeTypeEnum: string
{
    case Staff = 'Staff';
    case Client = 'Client';
}
