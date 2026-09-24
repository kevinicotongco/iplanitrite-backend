<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class SortRequestDto
{
    public function __construct(
        public string $id,
        public int $sortOrder,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            sortOrder: $data['sortOrder'],
        );
    }
}
