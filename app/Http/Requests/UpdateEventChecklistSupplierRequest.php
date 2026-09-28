<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateEventChecklistSupplierRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventChecklistSupplierRequest extends FormRequest
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
            'supplierId' => ['required', 'uuid'],
        ];
    }

    public function toDto(): UpdateEventChecklistSupplierRequestDto
    {
        return UpdateEventChecklistSupplierRequestDto::fromArray($this->validated());
    }
}
