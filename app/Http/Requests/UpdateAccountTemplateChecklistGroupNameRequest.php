<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateAccountTemplateChecklistGroupNameRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountTemplateChecklistGroupNameRequest extends FormRequest
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

    public function toDto(): UpdateAccountTemplateChecklistGroupNameRequestDto
    {
        return UpdateAccountTemplateChecklistGroupNameRequestDto::fromArray($this->validated());
    }
}
