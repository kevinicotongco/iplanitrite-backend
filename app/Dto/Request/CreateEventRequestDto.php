<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\EventTypeEnum;

readonly class CreateEventRequestDto
{
    /**
     * @param array<ClientCreateRequestDto> $clients
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public EventTypeEnum $eventType,
        public WeddingCelebrantsRequestDto|CelebrantRequestDto $celebrants,
        public WeddingSegmentsRequestDto|PrimaryEventSegmentRequestDto $segments,
        public array $clients,
        public ?string $thumbnailId,
        public ?string $dressCode,
        public ?string $theme,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $clients = array_map(
            fn(array $clientData) => ClientCreateRequestDto::fromArray($clientData),
            $data['clients'] ?? []
        );

        $eventType = EventTypeEnum::from($data['eventType']);
        
        // Parse celebrants based on event type
        if ($eventType === EventTypeEnum::Wedding) {
            $celebrants = WeddingCelebrantsRequestDto::fromArray($data['celebrants']);
            $segments = WeddingSegmentsRequestDto::fromArray($data['segments']);
        } else {
            $celebrants = CelebrantRequestDto::fromArray($data['celebrant']);
            $segments = PrimaryEventSegmentRequestDto::fromArray($data['segment']);
        }

        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            eventType: $eventType,
            celebrants: $celebrants,
            segments: $segments,
            clients: $clients,
            thumbnailId: $data['thumbnailId'] ?? null,
            dressCode: $data['dressCode'] ?? null,
            theme: $data['theme'] ?? null,
        );
    }
}
