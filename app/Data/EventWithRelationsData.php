<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Models\Event;

final readonly class EventWithRelationsData
{
    /**
     * @param array<EventSegmentData> $primarySegments
     */
    public function __construct(
        public string                      $id,
        public string                      $accountId,
        public string                      $name,
        public ?string                     $description,
        public EventStatusEnum             $status,
        public EventTypeEnum               $eventType,
        public ?CelebrantWithRelationsData $celebrantOne,
        public ?CelebrantWithRelationsData $celebrantTwo,
        public array                       $primarySegments,
    ) {}

    public static function fromModel(Event $event): self
    {
        $primarySegments = $event->primarySegments
            ->map(fn($segment) => EventSegmentData::fromModel($segment))
            ->toArray();

        return new self(
            id: $event->id,
            accountId: $event->account_id,
            name: $event->name,
            description: $event->description,
            status: $event->status,
            eventType: $event->event_type,
            celebrantOne: $event->celebrantOne ? CelebrantWithRelationsData::fromModel($event->celebrantOne) : null,
            celebrantTwo: $event->celebrantTwo ? CelebrantWithRelationsData::fromModel($event->celebrantTwo) : null,
            primarySegments: $primarySegments,
        );
    }
}
