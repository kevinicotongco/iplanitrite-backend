<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountBankDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountBankDetail>
 */
class AccountBankDetailFactory extends Factory
{
    protected $model = AccountBankDetail::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'bank_name' => fake()->company() . ' Bank',
            'account_number' => fake()->numerify('##########'),
            'qr_code' => null,
        ];
    }
}
