<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enums\SupplierRolePermissionEnum;

readonly class SupplierRoleRequestDto
{
    /**
     * @param array<SupplierRolePermissionEnum> $permissions
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
            fn($permission) => SupplierRolePermissionEnum::from($permission),
            $data['permissions'] ?? []
        );

        return new self(
            name: $data['name'],
            permissions: $permissions,
        );
    }
}
