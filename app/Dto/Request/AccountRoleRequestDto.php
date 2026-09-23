<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\AccountRolePermissionEnum;

readonly class AccountRoleRequestDto
{
    /**
     * @param array<AccountRolePermissionEnum> $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $permissions = array_map(
            fn($permission) => AccountRolePermissionEnum::from($permission),
            $data['permissions'] ?? []
        );

        return new self(
            name: $data['name'],
            permissions: $permissions,
        );
    }
}
