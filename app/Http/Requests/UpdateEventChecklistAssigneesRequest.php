<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\EventChecklistUpdateAssigneeRequestDto;
use App\Enums\EventChecklistAssigneeTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEventChecklistAssigneesRequest extends FormRequest
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
            '*.assigneeId' => ['required', 'uuid'],
            '*.assigneeType' => ['required', 'string', Rule::enum(EventChecklistAssigneeTypeEnum::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assignees = $this->all();

            if (!array_is_list($assignees)) {
                $validator->errors()->add('assignees', 'The request body must be a list of assignees.');
                return;
            }

            $seen = [];
            foreach ($assignees as $index => $assignee) {
                if (!is_array($assignee) || !isset($assignee['assigneeId'], $assignee['assigneeType'])) {
                    continue;
                }

                $key = $assignee['assigneeType'] . ':' . $assignee['assigneeId'];
                if (isset($seen[$key])) {
                    $validator->errors()->add($index . '.assigneeId', 'The assignee has already been listed.');
                }
                $seen[$key] = true;
            }
        });
    }

    /**
     * @return array<int, EventChecklistUpdateAssigneeRequestDto>
     */
    public function toDtos(): array
    {
        return array_values(array_map(
            fn(array $assignee): EventChecklistUpdateAssigneeRequestDto => EventChecklistUpdateAssigneeRequestDto::fromArray($assignee),
            $this->validated()
        ));
    }
}
