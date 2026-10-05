<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\AccountBankDetailRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class AccountBankDetailRequest extends FormRequest
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
            'bankName' => ['required', 'string', 'max:255'],
            'accountNumber' => ['required', 'string', 'max:255'],
            'qrCode' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function toDto(): AccountBankDetailRequestDto
    {
        return AccountBankDetailRequestDto::fromArray($this->validated());
    }
}
