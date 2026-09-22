<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\EventStatusEnum;

readonly class UpdateEventRequestDto
{
    public function __construct(
        public string $name,
        public ?string $description,
        public EventStatusEnum $status,
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
            eventDate: $data['eventDate'],
            celebrantOne: CelebrantRequestDto::fromArray($data['celebrantOne']),
            celebrantTwo: isset($data['celebrantTwo']) ? CelebrantRequestDto::fromArray($data['celebrantTwo']) : null,
            address: AddressRequestDto::fromArray($data['address']),
        );
    }
}
