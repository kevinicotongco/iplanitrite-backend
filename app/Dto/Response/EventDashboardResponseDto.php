<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Event;

readonly class EventDashboardResponseDto
{
    /**
     * @param array<ClientResponseDto> $clients
     * @param array<EventDocumentGroupResponseDto> $documents
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $dressCode,
        public ?string $theme,
        public ?AddressResponseDto $address,
        public array $clients,
        public ?DocumentResponseDto $thumbnail,
        public array $documents,
        public string $status,
        public string $eventType,
        public CelebrantResponseDto $celebrantOne,
        public ?CelebrantResponseDto $celebrantTwo,
        public EventSegmentResponseDto $primarySegment,
        public GuestCountResponseDto $guestsCount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'dressCode' => $this->dressCode,
            'theme' => $this->theme,
            'address' => $this->address?->toArray(),
            'clients' => array_map(fn(ClientResponseDto $client) => $client->toArray(), $this->clients),
            'thumbnail' => $this->thumbnail?->toArray(),
            'documents' => array_map(fn(EventDocumentGroupResponseDto $group) => $group->toArray(), $this->documents),
            'status' => $this->status,
            'eventType' => $this->eventType,
            'celebrantOne' => $this->celebrantOne->toArray(),
            'celebrantTwo' => $this->celebrantTwo?->toArray(),
            'primarySegment' => $this->primarySegment->toArray(),
            'guestsCount' => $this->guestsCount->toArray(),
        ];
    }
}
