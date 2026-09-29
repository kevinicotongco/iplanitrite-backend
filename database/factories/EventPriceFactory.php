<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPrice>
 */
class EventPriceFactory extends Factory
{
    protected $model = EventPrice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'account_id' => fn(array $attributes): string => Event::findOrFail($attributes['event_id'])->account_id,
            'name' => fake()->words(3, true),
            'cost_price' => fake()->randomFloat(2, 0, 5000),
            'retail_price' => fake()->randomFloat(2, 0, 10000),
            'sort_order' => 0,
            'supplier_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
