<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\EventGuestCreateRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class CreateEventGuestRequest extends FormRequest
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
            'firstName' => ['required', 'string', 'max:255'],
            'middleName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): EventGuestCreateRequestDto
    {
        return EventGuestCreateRequestDto::fromArray($this->validated());
    }
}
