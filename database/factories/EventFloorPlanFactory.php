<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\FloorPlanCanvasData;
use App\Models\Event;
use App\Models\EventFloorPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventFloorPlan>
 */
class EventFloorPlanFactory extends Factory
{
    protected $model = EventFloorPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'canvas_state' => FloorPlanCanvasData::default()->toArray(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
