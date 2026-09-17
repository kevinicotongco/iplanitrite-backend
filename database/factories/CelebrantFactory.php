<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\Celebrant;
use App\Models\ContactNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Celebrant>
 */
class CelebrantFactory extends Factory
{
    protected $model = Celebrant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'profile_picture' => null,
            'address_id' => null,
            'contact_number_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function withAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'address_id' => Address::factory(),
        ]);
    }

    public function withContactNumber(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_number_id' => ContactNumber::factory(),
        ]);
    }
}
