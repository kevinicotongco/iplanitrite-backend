<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\CreatePaymentRequestDto;
use App\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:1000'],
            'paymentMethod' => ['required', 'string', Rule::enum(PaymentMethodEnum::class)],
            'accountBankDetailId' => [
                Rule::requiredIf(fn(): bool => $this->input('paymentMethod') === PaymentMethodEnum::BankTransfer->value),
                Rule::prohibitedIf(fn(): bool => $this->input('paymentMethod') === PaymentMethodEnum::Cash->value),
                'nullable',
                'uuid',
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'proofDocumentId' => ['nullable', 'uuid'],
        ];
    }

    public function toDto(): CreatePaymentRequestDto
    {
        return CreatePaymentRequestDto::fromArray($this->validated());
    }
}
