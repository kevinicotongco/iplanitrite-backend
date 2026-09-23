<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
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
        $staffId = $this->route('id');

        return [
            'email' => ['required', 'email', Rule::unique('supplier_staff', 'email')->ignore($staffId), 'max:255'],
            'firstName' => ['required', 'string', 'max:255'],
            'middleName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'contactNumber' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'uuid', 'exists:supplier_roles,id'],

            'address' => ['nullable', 'array'],
            'address.line1' => ['required_with:address', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state' => ['required_with:address', 'string', 'max:255'],
            'address.zip' => ['required_with:address', 'string', 'max:20'],
            'address.lat' => ['nullable', 'string', 'max:20'],
            'address.long' => ['nullable', 'string', 'max:20'],
        ];
    }
}
