<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateAccountTemplateChecklistResponsibilityRequestDto;
use App\Enums\ResponsibilityTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountTemplateChecklistResponsibilityRequest extends FormRequest
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
            'responsibilityType' => ['required', 'string', Rule::in(array_column(ResponsibilityTypeEnum::cases(), 'value'))],
        ];
    }

    public function toDto(): UpdateAccountTemplateChecklistResponsibilityRequestDto
    {
        return UpdateAccountTemplateChecklistResponsibilityRequestDto::fromArray($this->validated());
    }
}
