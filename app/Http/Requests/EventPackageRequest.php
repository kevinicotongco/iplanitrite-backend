<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\EventPackageRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class EventPackageRequest extends FormRequest
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
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    public function toDto(): EventPackageRequestDto
    {
        return EventPackageRequestDto::fromArray($this->validated());
    }
}
