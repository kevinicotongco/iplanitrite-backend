<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventWithRelationsData;
use App\Models\Event;

readonly class EventResponseDto
{
    /**
     * @param array<EventSegmentResponseDto> $primarySegments
     */
    public function __construct(
        public string                 $id,
        public string                 $accountId,
        public string                 $name,
        public ?string                $description,
        public string                 $status,
        public string                 $eventType,
        public CelebrantResponseDto   $celebrantOne,
        public ?CelebrantResponseDto  $celebrantTwo,
        public array                  $primarySegments,
        public ?DocumentResponseDto   $thumbnail,
        public ?string                $dressCode,
        public ?string                $theme,
    ) {}

    public static function fromModel(Event $event): self
    {
        $primarySegments = $event->primarySegments
            ->map(fn($segment) => EventSegmentResponseDto::fromModel($segment))
            ->toArray();

        return new self(
            id: $event->id,
            accountId: $event->account_id,
            name: $event->name,
            description: $event->description,
            status: $event->status->value,
            eventType: $event->event_type->value,
            celebrantOne: $event->celebrantOne
                ? CelebrantResponseDto::fromModel($event->celebrantOne)
                : null,
            celebrantTwo: $event->celebrantTwo
                ? CelebrantResponseDto::fromModel($event->celebrantTwo)
                : null,
            primarySegments: $primarySegments,
            thumbnail: $event->thumbnail
                ? DocumentResponseDto::fromModel($event->thumbnail)
                : null,
            dressCode: $event->dress_code,
            theme: $event->theme,
        );
    }

    public static function fromEventData(EventWithRelationsData $eventData): self
    {
        $primarySegments = array_map(
            fn($segmentData) => new EventSegmentResponseDto(
                id: $segmentData->id,
                name: $segmentData->name,
                isPrimary: $segmentData->isPrimary,
                date: $segmentData->date,
                startTime: $segmentData->startTime,
                endTime: $segmentData->endTime,
                address: $segmentData->address
                    ? AddressResponseDto::fromAddressData($segmentData->address)
                    : null,
            ),
            $eventData->primarySegments
        );

        return new self(
            id: $eventData->id,
            accountId: $eventData->accountId,
            name: $eventData->name,
            description: $eventData->description,
            status: $eventData->status->value,
            eventType: $eventData->eventType->value,
            celebrantOne: CelebrantResponseDto::fromCelebrantData($eventData->celebrantOne),
            celebrantTwo: $eventData->celebrantTwo
                ? CelebrantResponseDto::fromCelebrantData($eventData->celebrantTwo)
                : null,
            primarySegments: $primarySegments,
            thumbnail: null,
            dressCode: null,
            theme: null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'accountId' => $this->accountId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'eventType' => $this->eventType,
            'celebrantOne' => $this->celebrantOne?->toArray(),
            'celebrantTwo' => $this->celebrantTwo?->toArray(),
            'primarySegments' => array_map(fn($segment) => $segment->toArray(), $this->primarySegments),
            'thumbnail' => $this->thumbnail?->toArray(),
            'dressCode' => $this->dressCode,
            'theme' => $this->theme,
        ];
    }
}
