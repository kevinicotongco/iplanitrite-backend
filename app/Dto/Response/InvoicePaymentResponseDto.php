<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Enums\InvoicePaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Models\InvoicePayment;

readonly class InvoicePaymentResponseDto
{
    public function __construct(
        public string $id,
        public InvoicePaymentStatusEnum $status,
        public ?string $description,
        public PaymentMethodEnum $paymentMethod,
        public ?string $bankName,
        public ?string $accountNumber,
        public string $amount,
        public ?DocumentResponseDto $proofDocument,
    ) {}

    public static function fromModel(InvoicePayment $payment): self
    {
        return new self(
            id: $payment->id,
            status: $payment->status,
            description: $payment->description,
            paymentMethod: $payment->payment_method,
            bankName: $payment->bank_name,
            accountNumber: $payment->account_number,
            amount: $payment->amount,
            proofDocument: $payment->proofDocument
                ? DocumentResponseDto::fromModel($payment->proofDocument)
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'description' => $this->description,
            'paymentMethod' => $this->paymentMethod->value,
            'bankName' => $this->bankName,
            'accountNumber' => $this->accountNumber,
            'amount' => $this->amount,
            'proofDocument' => $this->proofDocument?->toArray(),
        ];
    }
}
