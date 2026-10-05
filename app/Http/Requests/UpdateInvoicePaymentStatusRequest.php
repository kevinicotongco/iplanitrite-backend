<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateInvoicePaymentStatusRequestDto;
use App\Enums\InvoicePaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoicePaymentStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                Rule::in([InvoicePaymentStatusEnum::Approved->value, InvoicePaymentStatusEnum::Rejected->value]),
            ],
        ];
    }

    public function toDto(): UpdateInvoicePaymentStatusRequestDto
    {
        return UpdateInvoicePaymentStatusRequestDto::fromArray($this->validated());
    }
}
