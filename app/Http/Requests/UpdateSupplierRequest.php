<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SupplierSubscriptionTierEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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
            'logo' => ['nullable', 'file', 'image', 'max:5120'],
            'description' => ['nullable', 'string'],
            'subscriptionTier' => ['required', 'string', Rule::in(array_column(SupplierSubscriptionTierEnum::cases(), 'value'))],
            'countryId' => ['required', 'uuid', 'exists:countries,id'],
            'timezone' => ['required', 'string', 'timezone'],

            'address' => ['nullable', 'array'],
            'address.line1' => ['required_with:address', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state' => ['required_with:address', 'string', 'max:255'],
            'address.zip' => ['required_with:address', 'string', 'max:20'],
            'address.lat' => ['nullable', 'string', 'max:20'],
            'address.long' => ['nullable', 'string', 'max:20'],

            'contactNumber' => ['nullable', 'string', 'max:50'],
        ];
    }
}
