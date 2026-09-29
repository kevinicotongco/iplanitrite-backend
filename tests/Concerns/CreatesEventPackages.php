<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\EventTypeEnum;
use App\Models\EventPackage;

trait CreatesEventPackages
{
    protected function eventPackageIdFor(string $accountId, EventTypeEnum $eventType): string
    {
        return EventPackage::factory()->create([
            'account_id' => $accountId,
            'event_type' => $eventType,
        ])->id;
    }
}
