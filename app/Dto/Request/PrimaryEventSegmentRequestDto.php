<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class PrimaryEventSegmentRequestDto
{
    public function __construct(
        public string $date,
        public string $startTime,
        public string $endTime,
        public AddressRequestDto $address,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'],
            startTime: $data['startTime'],
            endTime: $data['endTime'],
            address: AddressRequestDto::fromArray($data['address']),
        );
    }
}
