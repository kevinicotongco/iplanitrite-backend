<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Illuminate\Http\UploadedFile;

readonly class UploadDocumentRequestDto
{
    public function __construct(
        public UploadedFile $file,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            file: $data['file'],
        );
    }
}
