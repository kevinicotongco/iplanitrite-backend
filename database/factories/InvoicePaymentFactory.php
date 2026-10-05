<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoicePaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicePayment>
 */
class InvoicePaymentFactory extends Factory
{
    protected $model = InvoicePayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'status' => InvoicePaymentStatusEnum::ForReview,
            'description' => null,
            'payment_method' => PaymentMethodEnum::Cash,
            'bank_name' => null,
            'account_number' => null,
            'amount' => fake()->randomFloat(2, 1, 1000),
            'proof_document_id' => null,
        ];
    }
}
