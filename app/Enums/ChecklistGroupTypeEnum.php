<?php

declare(strict_types=1);

namespace App\Enums;

enum ChecklistGroupTypeEnum: string
{
    case Supplier = 'Supplier';
    case General = 'General';
}
