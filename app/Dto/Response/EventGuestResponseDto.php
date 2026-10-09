<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventGuest;

readonly class EventGuestResponseDto
{
    public function __construct(
        public string $id,
        public string $eventGuestGroupId,
        public int $order,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public string $status,
    ) {}

    public static function fromModel(EventGuest $eventGuest): self
    {
        return new self(
            id: $eventGuest->id,
            eventGuestGroupId: $eventGuest->event_guest_group_id,
            order: $eventGuest->sort_order,
            firstName: $eventGuest->first_name,
            middleName: $eventGuest->middle_name,
            lastName: $eventGuest->last_name,
            status: $eventGuest->status->value,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'eventGuestGroupId' => $this->eventGuestGroupId,
            'order' => $this->order,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'status' => $this->status,
        ];
    }
}
