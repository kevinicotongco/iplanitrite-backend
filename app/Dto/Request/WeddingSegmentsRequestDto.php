<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class WeddingSegmentsRequestDto
{
    public function __construct(
        public PrimaryEventSegmentRequestDto $wedding,
        public ?PrimaryEventSegmentRequestDto $reception,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wedding: PrimaryEventSegmentRequestDto::fromArray($data['wedding']),
            reception: isset($data['reception']) ? PrimaryEventSegmentRequestDto::fromArray($data['reception']) : null,
        );
    }
}
