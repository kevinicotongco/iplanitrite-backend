<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\LoginResult;

readonly class StaffLoginResponseDto
{
    public function __construct(
        public string $token,
        public StaffResponseDto $user,
    ) {}

    public static function fromLoginResult(LoginResult $result): self
    {
        return new self(
            token: $result->token,
            user: StaffResponseDto::fromModel($result->user),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'user' => $this->user->toArray(),
        ];
    }
}
