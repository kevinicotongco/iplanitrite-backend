<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UpdateInvoiceDueDateRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceDueDateRequest extends FormRequest
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
            'dueDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function toDto(): UpdateInvoiceDueDateRequestDto
    {
        return UpdateInvoiceDueDateRequestDto::fromArray($this->validated());
    }
}
