<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SupplierRolePermissionEnum;
use App\Models\SupplierRole;

final readonly class SupplierRoleData
{
    /**
     * @param array<SupplierRolePermissionEnum> $permissions
     */
    public function __construct(
        public string $id,
        public string $supplierId,
        public string $name,
        public array $permissions,
    ) {}

    public static function fromModel(SupplierRole $role): self
    {
        $permissions = $role->permissions
            ->map(fn($perm) => $perm->permission)
            ->toArray();

        return new self(
            id: $role->id,
            supplierId: $role->supplier_id,
            name: $role->name,
            permissions: $permissions,
        );
    }
}
