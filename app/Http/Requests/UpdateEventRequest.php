<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_column(EventStatusEnum::cases(), 'value'))],
            'eventType' => ['required', 'string', Rule::in(array_column(EventTypeEnum::cases(), 'value'))],
            'eventDate' => ['required', 'date'],

            'celebrantOne' => ['required', 'array'],
            'celebrantOne.firstName' => ['required', 'string', 'max:255'],
            'celebrantOne.middleName' => ['nullable', 'string', 'max:255'],
            'celebrantOne.lastName' => ['required', 'string', 'max:255'],
            'celebrantOne.address' => ['nullable', 'array'],
            'celebrantOne.address.line1' => ['required_with:celebrantOne.address', 'string', 'max:255'],
            'celebrantOne.address.line2' => ['nullable', 'string', 'max:255'],
            'celebrantOne.address.city' => ['required_with:celebrantOne.address', 'string', 'max:255'],
            'celebrantOne.address.state' => ['required_with:celebrantOne.address', 'string', 'max:255'],
            'celebrantOne.address.zip' => ['required_with:celebrantOne.address', 'string', 'max:20'],
            'celebrantOne.address.lat' => ['nullable', 'string', 'max:20'],
            'celebrantOne.address.long' => ['nullable', 'string', 'max:20'],
            'celebrantOne.contactNumber' => ['nullable', 'string', 'max:50'],

            'celebrantTwo' => ['nullable', 'array'],
            'celebrantTwo.firstName' => ['required_with:celebrantTwo', 'string', 'max:255'],
            'celebrantTwo.middleName' => ['nullable', 'string', 'max:255'],
            'celebrantTwo.lastName' => ['required_with:celebrantTwo', 'string', 'max:255'],
            'celebrantTwo.address' => ['nullable', 'array'],
            'celebrantTwo.address.line1' => ['required_with:celebrantTwo.address', 'string', 'max:255'],
            'celebrantTwo.address.line2' => ['nullable', 'string', 'max:255'],
            'celebrantTwo.address.city' => ['required_with:celebrantTwo.address', 'string', 'max:255'],
            'celebrantTwo.address.state' => ['required_with:celebrantTwo.address', 'string', 'max:255'],
            'celebrantTwo.address.zip' => ['required_with:celebrantTwo.address', 'string', 'max:20'],
            'celebrantTwo.address.lat' => ['nullable', 'string', 'max:20'],
            'celebrantTwo.address.long' => ['nullable', 'string', 'max:20'],
            'celebrantTwo.contactNumber' => ['nullable', 'string', 'max:50'],

            'address' => ['required', 'array'],
            'address.line1' => ['required', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.state' => ['required', 'string', 'max:255'],
            'address.zip' => ['required', 'string', 'max:20'],
            'address.lat' => ['nullable', 'string', 'max:20'],
            'address.long' => ['nullable', 'string', 'max:20'],
        ];
    }
}
