<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Document;

readonly class DocumentResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $displayName,
        public string $url,
        public int $size,
        public string $mimeType,
    ) {}

    public static function fromModel(Document $document): self
    {
        return new self(
            id: $document->id,
            name: $document->name,
            displayName: $document->display_name,
            url: $document->url,
            size: $document->size,
            mimeType: $document->mime_type,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'displayName' => $this->displayName,
            'url' => $this->url,
            'size' => $this->size,
            'mimeType' => $this->mimeType,
        ];
    }
}
