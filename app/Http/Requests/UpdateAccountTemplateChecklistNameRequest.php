<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateAccountTemplateChecklistNameRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountTemplateChecklistNameRequest extends FormRequest
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
        ];
    }

    public function toDto(): UpdateAccountTemplateChecklistNameRequestDto
    {
        return UpdateAccountTemplateChecklistNameRequestDto::fromArray($this->validated());
    }
}
