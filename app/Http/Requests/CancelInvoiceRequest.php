<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\CancelInvoiceRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class CancelInvoiceRequest extends FormRequest
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
            'cancellationNotes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toDto(): CancelInvoiceRequestDto
    {
        return CancelInvoiceRequestDto::fromArray($this->validated());
    }
}
