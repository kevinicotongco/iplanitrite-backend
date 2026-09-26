<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\EventThemeDocumentGroup;

readonly class EventDocumentGroupResponseDto
{
    /**
     * @param array<DocumentResponseDto> $documents
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $documents,
    ) {}

    public static function fromModel(EventThemeDocumentGroup $group): self
    {
        $documents = $group->themeDocuments()
            ->with('document')
            ->get()
            ->map(fn($themeDoc) => DocumentResponseDto::fromModel($themeDoc->document))
            ->toArray();

        return new self(
            id: $group->id,
            name: $group->name,
            documents: $documents,
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
            'documents' => array_map(fn(DocumentResponseDto $doc) => $doc->toArray(), $this->documents),
        ];
    }
}
