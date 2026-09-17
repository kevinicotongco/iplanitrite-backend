<?php

declare(strict_types=1);

namespace App\Enums;

enum EventTypeEnum: string
{
    case Wedding = 'Wedding';
    case Birthday = 'Birthday';
    case Baptism = 'Baptism';
    case Debut = 'Debut';
}
