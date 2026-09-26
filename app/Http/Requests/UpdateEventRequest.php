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
        $eventType = $this->input('eventType');
        $isWedding = $eventType === EventTypeEnum::Wedding->value;

        $baseRules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_column(EventStatusEnum::cases(), 'value'))],
            'eventType' => ['required', 'string', Rule::in(array_column(EventTypeEnum::cases(), 'value'))],
            'thumbnailId' => ['nullable', 'uuid', 'exists:documents,id'],
            'dressCode' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'string', 'max:255'],
        ];

        if ($isWedding) {
            return array_merge($baseRules, $this->weddingRules());
        }

        return array_merge($baseRules, $this->nonWeddingRules());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function weddingRules(): array
    {
        return [
            'celebrants' => ['required', 'array'],
            'celebrants.bride' => ['required', 'array'],
            'celebrants.bride.firstName' => ['required', 'string', 'max:255'],
            'celebrants.bride.middleName' => ['nullable', 'string', 'max:255'],
            'celebrants.bride.lastName' => ['required', 'string', 'max:255'],
            'celebrants.bride.address' => ['nullable', 'array'],
            'celebrants.bride.address.line1' => ['required_with:celebrants.bride.address', 'string', 'max:255'],
            'celebrants.bride.address.line2' => ['nullable', 'string', 'max:255'],
            'celebrants.bride.address.city' => ['required_with:celebrants.bride.address', 'string', 'max:255'],
            'celebrants.bride.address.state' => ['required_with:celebrants.bride.address', 'string', 'max:255'],
            'celebrants.bride.address.zip' => ['required_with:celebrants.bride.address', 'string', 'max:20'],
            'celebrants.bride.address.lat' => ['nullable', 'string', 'max:20'],
            'celebrants.bride.address.long' => ['nullable', 'string', 'max:20'],
            'celebrants.bride.contactNumber' => ['nullable', 'string', 'max:50'],

            'celebrants.groom' => ['required', 'array'],
            'celebrants.groom.firstName' => ['required', 'string', 'max:255'],
            'celebrants.groom.middleName' => ['nullable', 'string', 'max:255'],
            'celebrants.groom.lastName' => ['required', 'string', 'max:255'],
            'celebrants.groom.address' => ['nullable', 'array'],
            'celebrants.groom.address.line1' => ['required_with:celebrants.groom.address', 'string', 'max:255'],
            'celebrants.groom.address.line2' => ['nullable', 'string', 'max:255'],
            'celebrants.groom.address.city' => ['required_with:celebrants.groom.address', 'string', 'max:255'],
            'celebrants.groom.address.state' => ['required_with:celebrants.groom.address', 'string', 'max:255'],
            'celebrants.groom.address.zip' => ['required_with:celebrants.groom.address', 'string', 'max:20'],
            'celebrants.groom.address.lat' => ['nullable', 'string', 'max:20'],
            'celebrants.groom.address.long' => ['nullable', 'string', 'max:20'],
            'celebrants.groom.contactNumber' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function nonWeddingRules(): array
    {
        return [
            'celebrant' => ['required', 'array'],
            'celebrant.firstName' => ['required', 'string', 'max:255'],
            'celebrant.middleName' => ['nullable', 'string', 'max:255'],
            'celebrant.lastName' => ['required', 'string', 'max:255'],
            'celebrant.address' => ['nullable', 'array'],
            'celebrant.address.line1' => ['required_with:celebrant.address', 'string', 'max:255'],
            'celebrant.address.line2' => ['nullable', 'string', 'max:255'],
            'celebrant.address.city' => ['required_with:celebrant.address', 'string', 'max:255'],
            'celebrant.address.state' => ['required_with:celebrant.address', 'string', 'max:255'],
            'celebrant.address.zip' => ['required_with:celebrant.address', 'string', 'max:20'],
            'celebrant.address.lat' => ['nullable', 'string', 'max:20'],
            'celebrant.address.long' => ['nullable', 'string', 'max:20'],
            'celebrant.contactNumber' => ['nullable', 'string', 'max:50'],
        ];
    }
}
