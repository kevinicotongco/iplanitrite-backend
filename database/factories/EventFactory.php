<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatusEnum;
use App\Models\Address;
use App\Models\Celebrant;
use App\Models\Event;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(EventStatusEnum::cases()),
            'event_date' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'celebrant_one_id' => Celebrant::factory(),
            'celebrant_two_id' => null,
            'address_id' => Address::factory(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function withTwoCelebrants(): static
    {
        return $this->state(fn (array $attributes) => [
            'celebrant_two_id' => Celebrant::factory(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatusEnum::Pending,
        ]);
    }

    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatusEnum::Ongoing,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatusEnum::Completed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatusEnum::Cancelled,
        ]);
    }
}
