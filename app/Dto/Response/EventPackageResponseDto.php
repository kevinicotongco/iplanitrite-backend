<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\EventPackageData;
use App\Models\EventPackage;

readonly class EventPackageResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public float $price,
    ) {}

    public static function fromModel(EventPackage $eventPackage): self
    {
        return self::fromData(EventPackageData::fromModel($eventPackage));
    }

    public static function fromData(EventPackageData $eventPackageData): self
    {
        return new self(
            id: $eventPackageData->id,
            name: $eventPackageData->name,
            description: $eventPackageData->description,
            price: $eventPackageData->price,
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
            'description' => $this->description,
            'price' => $this->price,
        ];
    }
}
