<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class WeddingCelebrantsRequestDto
{
    public function __construct(
        public CelebrantRequestDto $bride,
        public CelebrantRequestDto $groom,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            bride: CelebrantRequestDto::fromArray($data['bride']),
            groom: CelebrantRequestDto::fromArray($data['groom']),
        );
    }
}
