<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FloorPlanObjectCategoryEnum;
use App\Enums\FloorPlanObjectShapeEnum;
use App\Enums\FloorPlanObjectTypeEnum;

final readonly class FloorPlanTableData implements FloorPlanObjectData
{
    /**
     * @param list<string> $seats
     */
    public function __construct(
        public string $id,
        public FloorPlanObjectTypeEnum $type,
        public string $label,
        public ?string $subtitle,
        public int|float $x,
        public int|float $y,
        public int|float $radius,
        public int|float $rotation,
        public int|float $scaleX,
        public int|float $scaleY,
        public int $zIndex,
        public string $fill,
        public string $stroke,
        public int|float $strokeWidth,
        public string $shadowColor,
        public int|float $shadowBlur,
        public int|float $shadowOffsetX,
        public int|float $shadowOffsetY,
        public int $seatCount,
        public int|float $seatRadius,
        public int|float $seatSpacing,
        public array $seats,
        public FloorPlanObjectShapeEnum $shape,
        public bool $isLocked,
        public bool $isVisible,
        public bool $isSelectable,
        public int $minSeats,
        public int $maxSeats,
        public ?string $notes,
    ) {}

    public function category(): FloorPlanObjectCategoryEnum
    {
        return FloorPlanObjectCategoryEnum::Table;
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
            radius: $data['radius'],
            rotation: $data['rotation'],
            scaleX: $data['scaleX'],
            scaleY: $data['scaleY'],
            zIndex: $data['zIndex'],
            fill: $data['fill'],
            stroke: $data['stroke'],
            strokeWidth: $data['strokeWidth'],
            shadowColor: $data['shadowColor'],
            shadowBlur: $data['shadowBlur'],
            shadowOffsetX: $data['shadowOffsetX'],
            shadowOffsetY: $data['shadowOffsetY'],
            seatCount: $data['seatCount'],
            seatRadius: $data['seatRadius'],
            seatSpacing: $data['seatSpacing'],
            seats: array_values($data['seats']),
            shape: FloorPlanObjectShapeEnum::from($data['shape']),
            isLocked: $data['isLocked'],
            isVisible: $data['isVisible'],
            isSelectable: $data['isSelectable'],
            minSeats: $data['minSeats'],
            maxSeats: $data['maxSeats'],
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
            'radius' => $this->radius,
            'rotation' => $this->rotation,
            'scaleX' => $this->scaleX,
            'scaleY' => $this->scaleY,
            'zIndex' => $this->zIndex,
            'fill' => $this->fill,
            'stroke' => $this->stroke,
            'strokeWidth' => $this->strokeWidth,
            'shadowColor' => $this->shadowColor,
            'shadowBlur' => $this->shadowBlur,
            'shadowOffsetX' => $this->shadowOffsetX,
            'shadowOffsetY' => $this->shadowOffsetY,
            'seatCount' => $this->seatCount,
            'seatRadius' => $this->seatRadius,
            'seatSpacing' => $this->seatSpacing,
            'seats' => $this->seats,
            'category' => $this->category()->value,
            'shape' => $this->shape->value,
            'isLocked' => $this->isLocked,
            'isVisible' => $this->isVisible,
            'isSelectable' => $this->isSelectable,
            'minSeats' => $this->minSeats,
            'maxSeats' => $this->maxSeats,
            'notes' => $this->notes,
        ];
    }
}
