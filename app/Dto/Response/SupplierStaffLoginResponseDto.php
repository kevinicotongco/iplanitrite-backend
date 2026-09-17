<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\LoginResult;

readonly class SupplierStaffLoginResponseDto
{
    public function __construct(
        public string $token,
        public SupplierStaffResponseDto $user,
    ) {}

    public static function fromLoginResult(LoginResult $result): self
    {
        return new self(
            token: $result->token,
            user: SupplierStaffResponseDto::fromModel($result->user),
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
