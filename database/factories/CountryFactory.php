<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'iso2_code' => fake()->countryCode(),
            'iso3_code' => strtoupper(fake()->lexify('???')),
            'language_locale' => 'en-US',
            'calling_code' => '+' . fake()->numberBetween(1, 999),
            'flag' => '🏴',
            'currency_code' => fake()->currencyCode(),
            'currency_name' => 'US Dollar',
            'currency_symbol' => '$',
        ];
    }
}
