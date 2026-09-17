<?php

declare(strict_types=1);

namespace App\Enums;

enum ChecklistTypeEnum: string
{
    case Supplier = 'Supplier';
    case General = 'General';
}
