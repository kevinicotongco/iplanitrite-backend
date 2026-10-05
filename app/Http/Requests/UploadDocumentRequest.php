<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dto\Request\UploadDocumentRequestDto;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    private const MAX_FILE_SIZE_KB = 5120;

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
            'file' => ['required', 'file', 'max:' . self::MAX_FILE_SIZE_KB],
        ];
    }

    public function toDto(): UploadDocumentRequestDto
    {
        return UploadDocumentRequestDto::fromArray($this->validated());
    }
}
