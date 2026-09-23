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
        public string $id,
        public string $supplierId,
        public string $name,
        public ?string $description,
        public string $status,
        public string $eventType,
        public ?CelebrantResponseDto $celebrantOne,
        public ?CelebrantResponseDto $celebrantTwo,
        public array $primarySegments,
    ) {}

    public static function fromModel(Event $event): self
    {
        $primarySegments = $event->primarySegments
            ->map(fn($segment) => EventSegmentResponseDto::fromModel($segment))
            ->toArray();

        return new self(
            id: $event->id,
            supplierId: $event->supplier_id,
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
            supplierId: $eventData->supplierId,
            name: $eventData->name,
            description: $eventData->description,
            status: $eventData->status->value,
            eventType: $eventData->eventType->value,
            celebrantOne: $eventData->celebrantOne
                ? CelebrantResponseDto::fromCelebrantData($eventData->celebrantOne)
                : null,
            celebrantTwo: $eventData->celebrantTwo
                ? CelebrantResponseDto::fromCelebrantData($eventData->celebrantTwo)
                : null,
            primarySegments: $primarySegments,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'supplierId' => $this->supplierId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'eventType' => $this->eventType,
            'celebrantOne' => $this->celebrantOne?->toArray(),
            'celebrantTwo' => $this->celebrantTwo?->toArray(),
            'primarySegments' => array_map(fn($segment) => $segment->toArray(), $this->primarySegments),
        ];
    }
}
