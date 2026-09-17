<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class AddressRequestDto
{
    public function __construct(
        public string $line1,
        public ?string $line2,
        public string $city,
        public string $state,
        public string $zip,
        public ?string $lat,
        public ?string $long,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            line1: $data['line1'],
            line2: $data['line2'] ?? null,
            city: $data['city'],
            state: $data['state'],
            zip: $data['zip'],
            lat: $data['lat'] ?? null,
            long: $data['long'] ?? null,
        );
    }
}
