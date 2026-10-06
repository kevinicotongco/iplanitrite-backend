<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Data\FloorPlanCanvasData;

readonly class UpdateEventFloorPlanRequestDto
{
    public function __construct(
        public FloorPlanCanvasData $canvas,
    ) {}

    /**
     * @param array<string, mixed> $canvas
     */
    public static function fromArray(array $canvas): self
    {
        return new self(FloorPlanCanvasData::fromArray($canvas));
    }
}
