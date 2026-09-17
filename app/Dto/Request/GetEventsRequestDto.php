<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\EventStatusEnum;

readonly class GetEventsRequestDto
{
    public function __construct(
        public ?string $searchText,
        public ?EventStatusEnum $status,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            searchText: $data['searchText'] ?? null,
            status: isset($data['status']) ? EventStatusEnum::from($data['status']) : null,
        );
    }
}
