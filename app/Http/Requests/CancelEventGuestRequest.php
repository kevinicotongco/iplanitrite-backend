<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\EventGuestCancelStatusRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class CancelEventGuestRequest extends FormRequest
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
            'statusReason' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): EventGuestCancelStatusRequestDto
    {
        return EventGuestCancelStatusRequestDto::fromArray($this->validated());
    }
}
