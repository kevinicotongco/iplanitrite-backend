<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventGuest;

readonly class EventGuestResponseDto
{
    public function __construct(
        public string $id,
        public string $eventGuestGroupId,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?DocumentResponseDto $profilePicture,
        public ?AddressResponseDto $address,
        public ?ContactNumberResponseDto $contactNumber,
        public string $status,
    ) {}

    public static function fromModel(EventGuest $eventGuest): self
    {
        return new self(
            id: $eventGuest->id,
            eventGuestGroupId: $eventGuest->event_guest_group_id,
            firstName: $eventGuest->first_name,
            middleName: $eventGuest->middle_name,
            lastName: $eventGuest->last_name,
            profilePicture: $eventGuest->profilePictureDocument
                ? DocumentResponseDto::fromModel($eventGuest->profilePictureDocument)
                : null,
            address: $eventGuest->address
                ? AddressResponseDto::fromModel($eventGuest->address)
                : null,
            contactNumber: $eventGuest->contactNumber
                ? ContactNumberResponseDto::fromModel($eventGuest->contactNumber)
                : null,
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
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'profilePicture' => $this->profilePicture?->toArray(),
            'address' => $this->address?->toArray(),
            'contactNumber' => $this->contactNumber?->toArray(),
            'status' => $this->status,
        ];
    }
}
