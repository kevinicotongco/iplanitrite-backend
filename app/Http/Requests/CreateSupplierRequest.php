<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\CreateSupplierRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateSupplierRequest extends FormRequest
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
            'companyName' => ['required', 'string', 'max:255'],
            'contactPerson' => ['required', 'string', 'max:255'],
            'contactNumber' => ['required', 'string', 'max:50'],

            'address' => ['required', 'array'],
            'address.line1' => ['required', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.state' => ['required', 'string', 'max:255'],
            'address.zip' => ['required', 'string', 'max:20'],
            'address.lat' => ['nullable', 'string', 'max:20'],
            'address.long' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function toDto(): CreateSupplierRequestDto
    {
        return CreateSupplierRequestDto::fromArray($this->validated());
    }
}
