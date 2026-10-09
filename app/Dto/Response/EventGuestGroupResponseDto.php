<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventGuestGroupWithGuestsData;
use App\Models\EventGuest;
use App\Models\EventGuestGroup;

readonly class EventGuestGroupResponseDto
{
    /**
     * @param array<int, EventGuestResponseDto> $guests
     */
    public function __construct(
        public string $id,
        public string $name,
        public int $order,
        public array $guests,
    ) {}

    public static function fromData(EventGuestGroupWithGuestsData $data): self
    {
        return new self(
            id: $data->group->id,
            name: $data->group->name,
            order: $data->group->sort_order,
            guests: $data->guests
                ->map(fn(EventGuest $guest): EventGuestResponseDto => EventGuestResponseDto::fromModel($guest))
                ->all(),
        );
    }

    public static function fromModel(EventGuestGroup $group): self
    {
        return new self(
            id: $group->id,
            name: $group->name,
            order: $group->sort_order,
            guests: [],
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
            'order' => $this->order,
            'guests' => array_map(
                fn(EventGuestResponseDto $guest): array => $guest->toArray(),
                $this->guests
            ),
        ];
    }
}
