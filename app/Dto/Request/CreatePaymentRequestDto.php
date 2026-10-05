<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\PaymentMethodEnum;

readonly class CreatePaymentRequestDto
{
    public function __construct(
        public ?string $description,
        public PaymentMethodEnum $paymentMethod,
        public ?string $accountBankDetailId,
        public string $amount,
        public ?string $proofDocumentId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'] ?? null,
            paymentMethod: PaymentMethodEnum::from($data['paymentMethod']),
            accountBankDetailId: $data['accountBankDetailId'] ?? null,
            amount: (string) $data['amount'],
            proofDocumentId: $data['proofDocumentId'] ?? null,
        );
    }
}
