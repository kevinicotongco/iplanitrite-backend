<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\CreateInvoiceItemRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceItemRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
        ];
    }

    public function toDto(): CreateInvoiceItemRequestDto
    {
        return CreateInvoiceItemRequestDto::fromArray($this->validated());
    }
}
