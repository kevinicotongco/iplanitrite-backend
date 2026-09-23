<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use App\Models\Account;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'status' => AccountStatusEnum::Active,
            'logo' => null,
            'description' => fake()->paragraph(),
            'address_id' => null,
            'country_id' => Country::factory(),
            'contact_number_id' => null,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => fake()->timezone(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
