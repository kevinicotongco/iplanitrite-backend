<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Rules\Base64ImageRule;
use Illuminate\Foundation\Http\FormRequest;

class StaffUpdateProfileRequestDto extends FormRequest
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
            'avatar' => ['nullable', 'string', new Base64ImageRule()],
            'firstName' => ['required'],
            'middleName' => ['nullable'],
            'lastName' => ['required'],
        ];
    }
}
