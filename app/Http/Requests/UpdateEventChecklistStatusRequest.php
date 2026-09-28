<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateEventChecklistStatusRequestDto;
use App\Enums\EventChecklistStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventChecklistStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::enum(EventChecklistStatusEnum::class)],
        ];
    }

    public function toDto(): UpdateEventChecklistStatusRequestDto
    {
        return UpdateEventChecklistStatusRequestDto::fromArray($this->validated());
    }
}
