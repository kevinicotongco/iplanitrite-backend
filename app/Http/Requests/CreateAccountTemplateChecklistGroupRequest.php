<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\CreateAccountTemplateChecklistGroupRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateAccountTemplateChecklistGroupRequest extends FormRequest
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

    public function toDto(): CreateAccountTemplateChecklistGroupRequestDto
    {
        return CreateAccountTemplateChecklistGroupRequestDto::fromArray($this->validated());
    }
}
