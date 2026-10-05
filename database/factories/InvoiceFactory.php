<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatusEnum;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'invoice_number' => 'INV-' . str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => InvoiceStatusEnum::Pending,
            'cancellation_notes' => null,
            'due_date' => now()->addWeeks(2)->toDateString(),
        ];
    }
}
