<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventTypeEnum;
use App\Models\Account;
use App\Models\EventPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPackage>
 */
class EventPackageFactory extends Factory
{
    protected $model = EventPackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(2, true) . ' Package',
            'event_type' => fake()->randomElement(EventTypeEnum::cases()),
            'price' => fake()->randomFloat(2, 1000, 100000),
            'description' => fake()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
