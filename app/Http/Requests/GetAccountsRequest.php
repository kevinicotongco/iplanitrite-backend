<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetAccountsRequest extends FormRequest
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
            'searchText' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_column(AccountStatusEnum::cases(), 'value'))],
            'tier' => ['nullable', 'string', Rule::in(array_column(AccountSubscriptionTierEnum::cases(), 'value'))],
            'countryId' => ['nullable', 'uuid', 'exists:countries,id'],
        ];
    }
}
