<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateAccountTemplateChecklistSupplierRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountTemplateChecklistSupplierRequest extends FormRequest
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
            'supplierId' => ['required', 'uuid', 'exists:suppliers,id'],
        ];
    }

    public function toDto(): UpdateAccountTemplateChecklistSupplierRequestDto
    {
        return UpdateAccountTemplateChecklistSupplierRequestDto::fromArray($this->validated());
    }
}
