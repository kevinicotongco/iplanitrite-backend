<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AccountRolePermissionEnum;
use App\Models\AccountRole;

final readonly class AccountRoleData
{
    /**
     * @param array<AccountRolePermissionEnum> $permissions
     */
    public function __construct(
        public string $id,
        public string $accountId,
        public string $name,
        public array  $permissions,
    ) {}

    public static function fromModel(AccountRole $role): self
    {
        $permissions = $role->permissions
            ->map(fn($perm) => $perm->permission)
            ->toArray();

        return new self(
            id: $role->id,
            accountId: $role->account_id,
            name: $role->name,
            permissions: $permissions,
        );
    }
}
