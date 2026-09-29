<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\EventPriceRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class EventPriceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'costPrice' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'retailPrice' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'supplierId' => ['nullable', 'uuid'],
        ];
    }

    public function toDto(): EventPriceRequestDto
    {
        return EventPriceRequestDto::fromArray($this->validated());
    }
}
