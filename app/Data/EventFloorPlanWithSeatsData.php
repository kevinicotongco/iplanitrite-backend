<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\EventFloorPlan;
use App\Models\EventSeat;
use Illuminate\Support\Collection;

final readonly class EventFloorPlanWithSeatsData
{
    /**
     * @param Collection<int, EventSeat> $seats
     */
    public function __construct(
        public EventFloorPlan $floorPlan,
        public Collection $seats,
    ) {}
}
