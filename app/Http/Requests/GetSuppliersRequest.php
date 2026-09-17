<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetSuppliersRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(array_column(SupplierStatusEnum::cases(), 'value'))],
            'tier' => ['nullable', 'string', Rule::in(array_column(SupplierSubscriptionTierEnum::cases(), 'value'))],
            'countryId' => ['nullable', 'uuid', 'exists:countries,id'],
        ];
    }
}
