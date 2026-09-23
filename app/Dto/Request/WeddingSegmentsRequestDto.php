<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class WeddingSegmentsRequestDto
{
    public function __construct(
        public InitialEventSegmentRequestDto $wedding,
        public ?InitialEventSegmentRequestDto $reception,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wedding: InitialEventSegmentRequestDto::fromArray($data['wedding']),
            reception: isset($data['reception']) ? InitialEventSegmentRequestDto::fromArray($data['reception']) : null,
        );
    }
}
