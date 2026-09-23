<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventSegment;

readonly class EventSegmentResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $isPrimary,
        public string $date,
        public string $startTime,
        public string $endTime,
        public ?AddressResponseDto $address,
    ) {}

    public static function fromModel(EventSegment $segment): self
    {
        return new self(
            id: $segment->id,
            name: $segment->name,
            isPrimary: $segment->is_primary,
            date: $segment->date->format('Y-m-d'),
            startTime: $segment->start_time,
            endTime: $segment->end_time,
            address: $segment->address ? AddressResponseDto::fromModel($segment->address) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isPrimary' => $this->isPrimary,
            'date' => $this->date,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'address' => $this->address?->toArray(),
        ];
    }
}
