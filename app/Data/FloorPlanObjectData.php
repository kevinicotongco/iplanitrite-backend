<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FloorPlanObjectCategoryEnum;

interface FloorPlanObjectData
{
    public function category(): FloorPlanObjectCategoryEnum;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
