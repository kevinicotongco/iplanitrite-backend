<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventChecklistGroupsByTypeData;
use App\Data\EventChecklistGroupWithChecklistsData;

readonly class EventChecklistGroupsByTypeResponseDto
{
    /**
     * @param array<int, EventChecklistGroupResponseDto> $supplier
     * @param array<int, EventChecklistGroupResponseDto> $general
     */
    public function __construct(
        public array $supplier,
        public array $general,
    ) {}

    public static function fromData(EventChecklistGroupsByTypeData $groupsData): self
    {
        $toResponse = fn(EventChecklistGroupWithChecklistsData $groupData): EventChecklistGroupResponseDto => EventChecklistGroupResponseDto::fromData($groupData);

        return new self(
            supplier: $groupsData->supplier->map($toResponse)->all(),
            general: $groupsData->general->map($toResponse)->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $toArray = fn(EventChecklistGroupResponseDto $group): array => $group->toArray();

        return [
            'supplier' => array_map($toArray, $this->supplier),
            'general' => array_map($toArray, $this->general),
        ];
    }
}
