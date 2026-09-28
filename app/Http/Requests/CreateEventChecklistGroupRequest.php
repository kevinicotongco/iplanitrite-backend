<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\CreateEventChecklistGroupRequestDto;
use App\Enums\ChecklistGroupTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventChecklistGroupRequest extends FormRequest
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
            'checklistType' => ['required', 'string', Rule::enum(ChecklistGroupTypeEnum::class)],
        ];
    }

    public function toDto(): CreateEventChecklistGroupRequestDto
    {
        return CreateEventChecklistGroupRequestDto::fromArray($this->validated());
    }
}
