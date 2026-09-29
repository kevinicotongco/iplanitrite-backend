<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\EventTypeEnum;
use App\Models\EventPackage;

final readonly class EventPackageData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $name,
        public EventTypeEnum $eventType,
        public float $price,
        public string $description,
    ) {}

    public static function fromModel(EventPackage $eventPackage): self
    {
        return new self(
            id: $eventPackage->id,
            accountId: $eventPackage->account_id,
            name: $eventPackage->name,
            eventType: $eventPackage->event_type,
            price: (float) $eventPackage->price,
            description: $eventPackage->description,
        );
    }
}
