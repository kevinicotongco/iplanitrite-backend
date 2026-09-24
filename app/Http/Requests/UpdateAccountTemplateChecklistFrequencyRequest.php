<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateAccountTemplateChecklistFrequencyRequestDto;
use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountTemplateChecklistFrequencyRequest extends FormRequest
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
            'frequencyType' => ['required', 'string', Rule::in(array_column(ChecklistFrequencyTypeEnum::cases(), 'value'))],
            'frequencyAnchor' => ['required', 'string', Rule::in(array_column(FrequencyAnchorEnum::cases(), 'value'))],
            'frequencyValue' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toDto(): UpdateAccountTemplateChecklistFrequencyRequestDto
    {
        return UpdateAccountTemplateChecklistFrequencyRequestDto::fromArray($this->validated());
    }
}
