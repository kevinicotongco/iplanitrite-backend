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
        public WeddingCelebrantsRequestDto|CelebrantRequestDto $celebrants,
        public ?string $thumbnailId,
        public ?string $dressCode,
        public ?string $theme,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $eventType = EventTypeEnum::from($data['eventType']);

        // Parse celebrants based on event type
        if ($eventType === EventTypeEnum::Wedding) {
            $celebrants = WeddingCelebrantsRequestDto::fromArray($data['celebrants']);
        } else {
            $celebrants = CelebrantRequestDto::fromArray($data['celebrant']);
        }

        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: EventStatusEnum::from($data['status']),
            eventType: $eventType,
            celebrants: $celebrants,
            thumbnailId: $data['thumbnailId'] ?? null,
            dressCode: $data['dressCode'] ?? null,
            theme: $data['theme'] ?? null,
        );
    }
}
