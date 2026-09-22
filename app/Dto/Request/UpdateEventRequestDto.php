<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;

readonly class UpdateEventRequestDto
{
    public function __construct(
        public string $name,
        public ?string $description,
        public EventStatusEnum $status,
        public EventTypeEnum $eventType,
        public string $eventDate,
        public CelebrantRequestDto $celebrantOne,
        public ?CelebrantRequestDto $celebrantTwo,
        public AddressRequestDto $address,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: EventStatusEnum::from($data['status']),
            eventType: EventTypeEnum::from($data['eventType']),
            eventDate: $data['eventDate'],
            celebrantOne: CelebrantRequestDto::fromArray($data['celebrantOne']),
            celebrantTwo: isset($data['celebrantTwo']) ? CelebrantRequestDto::fromArray($data['celebrantTwo']) : null,
            address: AddressRequestDto::fromArray($data['address']),
        );
    }
}
