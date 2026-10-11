<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\EventGuestAssignRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class AssignEventGuestRequest extends FormRequest
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
            'seatId' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): EventGuestAssignRequestDto
    {
        return EventGuestAssignRequestDto::fromArray($this->validated());
    }
}
