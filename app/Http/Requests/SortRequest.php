<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\SortRequestDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SortRequest extends FormRequest
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
            '*' => ['array'],
            '*.id' => ['required', 'uuid', 'distinct'],
            '*.sortOrder' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->all();

            if ($items === [] || !array_is_list($items)) {
                $validator->errors()->add('items', 'The request body must be a non-empty list of sort items.');
            }
        });
    }

    /**
     * @return array<int, SortRequestDto>
     */
    public function toDtos(): array
    {
        return array_values(array_map(
            fn(array $item): SortRequestDto => SortRequestDto::fromArray($item),
            $this->validated()
        ));
    }
}
