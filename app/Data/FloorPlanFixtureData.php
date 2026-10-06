<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FloorPlanObjectCategoryEnum;
use App\Enums\FloorPlanObjectShapeEnum;
use App\Enums\FloorPlanObjectTypeEnum;

final readonly class FloorPlanFixtureData implements FloorPlanObjectData
{
    public function __construct(
        public string $id,
        public FloorPlanObjectTypeEnum $type,
        public string $label,
        public ?string $subtitle,
        public int|float $x,
        public int|float $y,
        public int|float $width,
        public int|float $height,
        public int|float $rotation,
        public int|float $scaleX,
        public int|float $scaleY,
        public int $zIndex,
        public string $fill,
        public string $stroke,
        public int|float $strokeWidth,
        public int|float $cornerRadius,
        public FloorPlanObjectShapeEnum $shape,
        public bool $isLocked,
        public bool $isVisible,
        public bool $isSelectable,
        public ?string $notes,
    ) {}

    public function category(): FloorPlanObjectCategoryEnum
    {
        return FloorPlanObjectCategoryEnum::Fixture;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            type: FloorPlanObjectTypeEnum::from($data['type']),
            label: $data['label'],
            subtitle: $data['subtitle'] ?? null,
            x: $data['x'],
            y: $data['y'],
            width: $data['width'],
            height: $data['height'],
            rotation: $data['rotation'],
            scaleX: $data['scaleX'],
            scaleY: $data['scaleY'],
            zIndex: $data['zIndex'],
            fill: $data['fill'],
            stroke: $data['stroke'],
            strokeWidth: $data['strokeWidth'],
            cornerRadius: $data['cornerRadius'],
            shape: FloorPlanObjectShapeEnum::from($data['shape']),
            isLocked: $data['isLocked'],
            isVisible: $data['isVisible'],
            isSelectable: $data['isSelectable'],
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'label' => $this->label,
            'subtitle' => $this->subtitle,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'rotation' => $this->rotation,
            'scaleX' => $this->scaleX,
            'scaleY' => $this->scaleY,
            'zIndex' => $this->zIndex,
            'fill' => $this->fill,
            'stroke' => $this->stroke,
            'strokeWidth' => $this->strokeWidth,
            'cornerRadius' => $this->cornerRadius,
            'category' => $this->category()->value,
            'shape' => $this->shape->value,
            'isLocked' => $this->isLocked,
            'isVisible' => $this->isVisible,
            'isSelectable' => $this->isSelectable,
            'notes' => $this->notes,
        ];
    }
}
