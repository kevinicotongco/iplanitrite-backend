<?php

declare(strict_types=1);

namespace App\Dto\Response;

readonly class GuestCountResponseDto
{
    public function __construct(
        public int $confirmed,
        public int $pending,
        public int $declined,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'confirmed' => $this->confirmed,
            'pending' => $this->pending,
            'declined' => $this->declined,
        ];
    }
}
