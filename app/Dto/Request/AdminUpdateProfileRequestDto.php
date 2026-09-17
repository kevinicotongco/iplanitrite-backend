<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateProfileRequestDto extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['nullable'],
            'firstName' => ['required'],
            'lastName' => ['required'],
        ];
    }
}
