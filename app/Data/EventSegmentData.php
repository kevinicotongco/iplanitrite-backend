<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\EventSegment;

final readonly class EventSegmentData
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $isPrimary,
        public string $date,
        public string $startTime,
        public string $endTime,
        public ?AddressData $address,
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
            address: $segment->address ? AddressData::fromModel($segment->address) : null,
        );
    }
}
