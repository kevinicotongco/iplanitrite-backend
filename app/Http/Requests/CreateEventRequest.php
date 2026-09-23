<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EventTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventRequest extends FormRequest
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
            'eventType' => ['required', 'string', Rule::in(array_column(EventTypeEnum::cases(), 'value'))],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*.email' => ['required', 'email', 'max:255'],
            'clients.*.firstName' => ['required', 'string', 'max:255'],
            'clients.*.lastName' => ['required', 'string', 'max:255'],
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
            // Wedding celebrants (bride and groom)
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

            // Wedding segments (wedding and optional reception)
            'segments' => ['required', 'array'],
            'segments.wedding' => ['required', 'array'],
            'segments.wedding.date' => ['required', 'date'],
            'segments.wedding.startTime' => ['required', 'string'],
            'segments.wedding.endTime' => ['required', 'string'],
            'segments.wedding.address' => ['required', 'array'],
            'segments.wedding.address.line1' => ['required', 'string', 'max:255'],
            'segments.wedding.address.line2' => ['nullable', 'string', 'max:255'],
            'segments.wedding.address.city' => ['required', 'string', 'max:255'],
            'segments.wedding.address.state' => ['required', 'string', 'max:255'],
            'segments.wedding.address.zip' => ['required', 'string', 'max:20'],
            'segments.wedding.address.lat' => ['nullable', 'string', 'max:20'],
            'segments.wedding.address.long' => ['nullable', 'string', 'max:20'],

            'segments.reception' => ['nullable', 'array'],
            'segments.reception.date' => ['required_with:segments.reception', 'date'],
            'segments.reception.startTime' => ['required_with:segments.reception', 'string'],
            'segments.reception.endTime' => ['required_with:segments.reception', 'string'],
            'segments.reception.address' => ['required_with:segments.reception', 'array'],
            'segments.reception.address.line1' => ['required_with:segments.reception.address', 'string', 'max:255'],
            'segments.reception.address.line2' => ['nullable', 'string', 'max:255'],
            'segments.reception.address.city' => ['required_with:segments.reception.address', 'string', 'max:255'],
            'segments.reception.address.state' => ['required_with:segments.reception.address', 'string', 'max:255'],
            'segments.reception.address.zip' => ['required_with:segments.reception.address', 'string', 'max:20'],
            'segments.reception.address.lat' => ['nullable', 'string', 'max:20'],
            'segments.reception.address.long' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function nonWeddingRules(): array
    {
        return [
            // Single celebrant for non-wedding events
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

            // Single segment for non-wedding events
            'segment' => ['required', 'array'],
            'segment.date' => ['required', 'date'],
            'segment.startTime' => ['required', 'string'],
            'segment.endTime' => ['required', 'string'],
            'segment.address' => ['required', 'array'],
            'segment.address.line1' => ['required', 'string', 'max:255'],
            'segment.address.line2' => ['nullable', 'string', 'max:255'],
            'segment.address.city' => ['required', 'string', 'max:255'],
            'segment.address.state' => ['required', 'string', 'max:255'],
            'segment.address.zip' => ['required', 'string', 'max:20'],
            'segment.address.lat' => ['nullable', 'string', 'max:20'],
            'segment.address.long' => ['nullable', 'string', 'max:20'],
        ];
    }
}
