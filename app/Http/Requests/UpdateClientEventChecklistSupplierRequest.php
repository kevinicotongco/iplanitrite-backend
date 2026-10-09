<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\ClientEventChecklistSupplierRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientEventChecklistSupplierRequest extends FormRequest
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
            'supplierId' => ['present', 'nullable', 'uuid'],
        ];
    }

    public function toDto(): ClientEventChecklistSupplierRequestDto
    {
        return ClientEventChecklistSupplierRequestDto::fromArray($this->validated());
    }
}
