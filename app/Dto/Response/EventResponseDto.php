<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Event;

readonly class EventResponseDto
{
    public function __construct(
        public string $id,
        public string $supplierId,
        public string $name,
        public ?string $description,
        public string $status,
        public string $eventDate,
        public ?CelebrantResponseDto $celebrantOne,
        public ?CelebrantResponseDto $celebrantTwo,
        public ?AddressResponseDto $address,
    ) {}

    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            supplierId: $event->supplier_id,
            name: $event->name,
            description: $event->description,
            status: $event->status->value,
            eventDate: $event->event_date->toIso8601String(),
            celebrantOne: $event->celebrantOne
                ? CelebrantResponseDto::fromModel($event->celebrantOne)
                : null,
            celebrantTwo: $event->celebrantTwo
                ? CelebrantResponseDto::fromModel($event->celebrantTwo)
                : null,
            address: $event->address
                ? AddressResponseDto::fromModel($event->address)
                : null,
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
            'eventDate' => $this->eventDate,
            'celebrantOne' => $this->celebrantOne?->toArray(),
            'celebrantTwo' => $this->celebrantTwo?->toArray(),
            'address' => $this->address?->toArray(),
        ];
    }
}
