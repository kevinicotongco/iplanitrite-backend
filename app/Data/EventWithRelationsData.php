<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Models\Event;

final readonly class EventWithRelationsData
{
    public function __construct(
        public string $id,
        public string $supplierId,
        public string $name,
        public ?string $description,
        public EventStatusEnum $status,
        public EventTypeEnum $eventType,
        public string $eventDate,
        public ?CelebrantWithRelationsData $celebrantOne,
        public ?CelebrantWithRelationsData $celebrantTwo,
        public ?AddressData $address,
    ) {}

    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            supplierId: $event->supplier_id,
            name: $event->name,
            description: $event->description,
            status: $event->status,
            eventType: $event->event_type,
            eventDate: $event->event_date->toIso8601String(),
            celebrantOne: $event->celebrantOne ? CelebrantWithRelationsData::fromModel($event->celebrantOne) : null,
            celebrantTwo: $event->celebrantTwo ? CelebrantWithRelationsData::fromModel($event->celebrantTwo) : null,
            address: $event->address ? AddressData::fromModel($event->address) : null,
        );
    }
}
