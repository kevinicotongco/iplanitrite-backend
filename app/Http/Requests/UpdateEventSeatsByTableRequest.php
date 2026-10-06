<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\EventSeatAssignmentRequestDto;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventSeatsByTableRequest extends FormRequest
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
            '*.eventGuestId' => ['required', 'uuid', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!array_is_list($this->all())) {
                $validator->errors()->add('guests', 'The body must be a list of guests.');
            }
        });
    }

    /**
     * @return list<EventSeatAssignmentRequestDto>
     */
    public function toDtos(): array
    {
        return array_map(
            fn(array $item): EventSeatAssignmentRequestDto => EventSeatAssignmentRequestDto::fromArray($item),
            array_values($this->validated()),
        );
    }
}
