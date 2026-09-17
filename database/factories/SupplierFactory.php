<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use App\Models\Country;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'status' => SupplierStatusEnum::Active,
            'logo' => null,
            'description' => fake()->paragraph(),
            'address_id' => null,
            'country_id' => Country::factory(),
            'contact_number_id' => null,
            'subscription_tier' => SupplierSubscriptionTierEnum::Free,
            'timezone' => fake()->timezone(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
