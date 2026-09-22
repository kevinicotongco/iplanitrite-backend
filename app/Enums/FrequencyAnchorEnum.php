<?php

declare(strict_types=1);

namespace App\Enums;

enum FrequencyAnchorEnum: string
{
    case AfterCreation = 'AfterCreation';
    case BeforeEvent = 'BeforeEvent';
}
