<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\FloorPlanCanvasData;
use App\Dto\Request\UpdateEventFloorPlanRequestDto;
use App\Enums\FloorPlanObjectCategoryEnum;
use App\Enums\FloorPlanObjectShapeEnum;
use App\Enums\FloorPlanObjectTypeEnum;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventFloorPlanRequest extends FormRequest
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
        $rules = [
            'canvas' => ['required', 'array'],
            'canvas.version' => ['required', 'string', 'max:20'],
            'canvas.canvas' => ['required', 'array'],
            'canvas.canvas.width' => ['required', $this->strictNumber()],
            'canvas.canvas.height' => ['required', $this->strictNumber()],
            'canvas.canvas.zoom' => ['required', $this->strictNumber()],
            'canvas.canvas.gridSize' => ['required', $this->strictNumber()],
            'canvas.canvas.snapToGrid' => ['required', $this->strictBoolean()],
            'canvas.canvas.backgroundColor' => ['required', 'string', 'max:255'],
            'canvas.objects' => ['present', 'array'],
        ];

        $objects = $this->input('canvas.objects');

        if (!is_array($objects)) {
            return $rules;
        }

        foreach ($objects as $index => $object) {
            $path = 'canvas.objects.' . $index;
            $rules[$path] = ['array'];

            if (!is_array($object)) {
                continue;
            }

            $rules += $this->commonObjectRules($path);

            $category = FloorPlanObjectCategoryEnum::tryFrom((string) ($object['category'] ?? ''));

            $rules += match ($category) {
                FloorPlanObjectCategoryEnum::Table => $this->tableRules($path),
                FloorPlanObjectCategoryEnum::Fixture => $this->fixtureRules($path),
                null => [],
            };
        }

        $rules['canvas.objects.*.id'] = ['distinct'];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $seenSeatIds = [];

            foreach ($this->input('canvas.objects') as $index => $object) {
                if ($object['category'] !== FloorPlanObjectCategoryEnum::Table->value) {
                    continue;
                }

                $path = 'canvas.objects.' . $index;
                $seats = $object['seats'];

                if ($object['seatCount'] !== count($seats)) {
                    $validator->errors()->add($path . '.seatCount', 'The seat count must match the number of seats.');
                }

                if (count($seats) < $object['minSeats']) {
                    $validator->errors()->add($path . '.seatCount', 'The seat count is below the minimum seats.');
                }

                if (count($seats) > $object['maxSeats']) {
                    $validator->errors()->add($path . '.seatCount', 'The seat count is above the maximum seats.');
                }

                foreach ($seats as $seatIndex => $seatId) {
                    if (isset($seenSeatIds[$seatId])) {
                        $validator->errors()->add($path . '.seats.' . $seatIndex, 'The seat id must be unique across the canvas.');
                    }

                    $seenSeatIds[$seatId] = true;
                }
            }
        });
    }

    public function toDto(): UpdateEventFloorPlanRequestDto
    {
        return UpdateEventFloorPlanRequestDto::fromArray($this->input('canvas'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function commonObjectRules(string $path): array
    {
        return [
            $path . '.id' => ['required', 'string', 'max:255'],
            $path . '.type' => ['required', Rule::enum(FloorPlanObjectTypeEnum::class)],
            $path . '.category' => ['required', Rule::enum(FloorPlanObjectCategoryEnum::class)],
            $path . '.shape' => ['required', Rule::enum(FloorPlanObjectShapeEnum::class)],
            $path . '.label' => ['required', 'string', 'max:255'],
            $path . '.subtitle' => ['present', 'nullable', 'string', 'max:255'],
            $path . '.x' => ['required', $this->strictNumber()],
            $path . '.y' => ['required', $this->strictNumber()],
            $path . '.rotation' => ['required', $this->strictNumber()],
            $path . '.scaleX' => ['required', $this->strictNumber()],
            $path . '.scaleY' => ['required', $this->strictNumber()],
            $path . '.zIndex' => ['required', $this->strictInteger()],
            $path . '.fill' => ['required', 'string', 'max:255'],
            $path . '.stroke' => ['required', 'string', 'max:255'],
            $path . '.strokeWidth' => ['required', $this->strictNumber()],
            $path . '.isLocked' => ['required', $this->strictBoolean()],
            $path . '.isVisible' => ['required', $this->strictBoolean()],
            $path . '.isSelectable' => ['required', $this->strictBoolean()],
            $path . '.notes' => ['present', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function tableRules(string $path): array
    {
        return [
            $path . '.radius' => ['required', $this->strictNumber()],
            $path . '.shadowColor' => ['required', 'string', 'max:255'],
            $path . '.shadowBlur' => ['required', $this->strictNumber()],
            $path . '.shadowOffsetX' => ['required', $this->strictNumber()],
            $path . '.shadowOffsetY' => ['required', $this->strictNumber()],
            $path . '.seatCount' => ['required', $this->strictInteger()],
            $path . '.seatRadius' => ['required', $this->strictNumber()],
            $path . '.seatSpacing' => ['required', $this->strictNumber()],
            $path . '.seats' => ['present', 'array', $this->strictList()],
            $path . '.seats.*' => ['string', 'max:255'],
            $path . '.minSeats' => ['required', $this->strictInteger()],
            $path . '.maxSeats' => ['required', $this->strictInteger()],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function fixtureRules(string $path): array
    {
        return [
            $path . '.width' => ['required', $this->strictNumber()],
            $path . '.height' => ['required', $this->strictNumber()],
            $path . '.cornerRadius' => ['required', $this->strictNumber()],
        ];
    }

    private function strictNumber(): Closure
    {
        return fn(string $attribute, mixed $value, Closure $fail) => is_int($value) || is_float($value)
            ?: $fail('The :attribute must be a number.');
    }

    private function strictInteger(): Closure
    {
        return fn(string $attribute, mixed $value, Closure $fail) => is_int($value)
            ?: $fail('The :attribute must be an integer.');
    }

    private function strictBoolean(): Closure
    {
        return fn(string $attribute, mixed $value, Closure $fail) => is_bool($value)
            ?: $fail('The :attribute must be true or false.');
    }

    private function strictList(): Closure
    {
        return fn(string $attribute, mixed $value, Closure $fail) => array_is_list($value)
            ?: $fail('The :attribute must be a list.');
    }
}
