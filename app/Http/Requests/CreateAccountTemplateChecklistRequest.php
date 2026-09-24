<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\CreateAccountTemplateChecklistRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateAccountTemplateChecklistRequest extends FormRequest
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

    public function toDto(): CreateAccountTemplateChecklistRequestDto
    {
        return CreateAccountTemplateChecklistRequestDto::fromArray($this->validated());
    }
}
