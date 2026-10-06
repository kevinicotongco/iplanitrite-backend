<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FloorPlanObjectCategoryEnum;

final readonly class FloorPlanCanvasData
{
    public const DEFAULT_VERSION = '1.0';

    /**
     * @param list<FloorPlanObjectData> $objects
     */
    public function __construct(
        public string $version,
        public FloorPlanCanvasSettingsData $settings,
        public array $objects,
    ) {}

    public static function default(): self
    {
        return new self(
            version: self::DEFAULT_VERSION,
            settings: FloorPlanCanvasSettingsData::default(),
            objects: [],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            version: $data['version'],
            settings: FloorPlanCanvasSettingsData::fromArray($data['canvas']),
            objects: array_map(
                fn(array $object): FloorPlanObjectData => match (FloorPlanObjectCategoryEnum::from($object['category'])) {
                    FloorPlanObjectCategoryEnum::Table => FloorPlanTableData::fromArray($object),
                    FloorPlanObjectCategoryEnum::Fixture => FloorPlanFixtureData::fromArray($object),
                },
                array_values($data['objects']),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'canvas' => $this->settings->toArray(),
            'objects' => array_map(
                fn(FloorPlanObjectData $object): array => $object->toArray(),
                $this->objects,
            ),
        ];
    }

    /**
     * @return list<FloorPlanTableData>
     */
    public function tables(): array
    {
        return array_values(array_filter(
            $this->objects,
            fn(FloorPlanObjectData $object): bool => $object instanceof FloorPlanTableData,
        ));
    }

    public function findTable(string $tableId): ?FloorPlanTableData
    {
        foreach ($this->tables() as $table) {
            if ($table->id === $tableId) {
                return $table;
            }
        }

        return null;
    }

    public function hasSeat(string $tableId, string $seatId): bool
    {
        return in_array($seatId, $this->findTable($tableId)?->seats ?? [], true);
    }
}
