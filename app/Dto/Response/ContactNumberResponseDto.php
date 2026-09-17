<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\ContactNumber;

readonly class ContactNumberResponseDto
{
    public function __construct(
        public string $id,
        public string $number,
        public CountryResponseDto $country,
    ) {}

    public static function fromModel(ContactNumber $contactNumber): self
    {
        return new self(
            id: $contactNumber->id,
            number: $contactNumber->number,
            country: CountryResponseDto::fromModel($contactNumber->country),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'country' => $this->country->toArray(),
        ];
    }
}
