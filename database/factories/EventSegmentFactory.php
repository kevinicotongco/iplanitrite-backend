<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\Event;
use App\Models\EventSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSegment>
 */
class EventSegmentFactory extends Factory
{
    protected $model = EventSegment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->time();
        $endTime = fake()->time();

        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Wedding', 'Reception', 'Birthday', 'Baptism', 'Debut']),
            'is_primary' => \DB::raw('true'),
            'date' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'address_id' => Address::factory(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => \DB::raw('true'),
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => \DB::raw('false'),
        ]);
    }
}
