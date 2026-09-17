<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContactNumber;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactNumber>
 */
class ContactNumberFactory extends Factory
{
    protected $model = ContactNumber::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->phoneNumber(),
            'country_id' => Country::factory(),
        ];
    }
}
