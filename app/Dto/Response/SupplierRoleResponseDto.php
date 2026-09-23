<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\SupplierRoleData;
use App\Models\SupplierRole;

readonly class SupplierRoleResponseDto
{
    /**
     * @param array<string> $permissions
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $permissions,
    ) {}

    public static function fromModel(SupplierRole $role): self
    {
        $permissions = $role->permissions
            ->map(fn($perm) => $perm->permission->value)
            ->toArray();

        return new self(
            id: $role->id,
            name: $role->name,
            permissions: $permissions,
        );
    }

    public static function fromData(SupplierRoleData $data): self
    {
        $permissions = array_map(fn($perm) => $perm->value, $data->permissions);

        return new self(
            id: $data->id,
            name: $data->name,
            permissions: $permissions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions,
        ];
    }
}
