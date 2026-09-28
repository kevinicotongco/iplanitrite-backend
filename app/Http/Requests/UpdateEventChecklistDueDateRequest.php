<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateEventChecklistDueDateRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventChecklistDueDateRequest extends FormRequest
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
            'dueDate' => ['present', 'nullable', 'date_format:' . UpdateEventChecklistDueDateRequestDto::DATE_FORMAT],
        ];
    }

    public function toDto(): UpdateEventChecklistDueDateRequestDto
    {
        return UpdateEventChecklistDueDateRequestDto::fromArray($this->validated());
    }
}
