<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Data\SupplierRoleData;
use App\Dto\Request\SupplierRoleRequestDto;
use App\Models\SupplierRole;
use App\Models\SupplierRolePermission;
use App\Models\SupplierStaff;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class SupplierRoleService
{
    public function __construct(
        private SupplierStaff $authenticatedUser,
    ) {}

    /**
     * Get all roles for a supplier
     *
     * @param string $supplierId
     * @return Collection<SupplierRoleData>
     */
    public function getRoles(string $supplierId): Collection
    {
        $roles = SupplierRole::where('supplier_id', $supplierId)
            ->with('permissions')
            ->get();

        return $roles->map(fn($role) => SupplierRoleData::fromModel($role));
    }

    /**
     * Create a new supplier role with permissions
     *
     * @param string $supplierId
     * @param SupplierRoleRequestDto $dto
     * @return void
     */
    public function createRole(string $supplierId, SupplierRoleRequestDto $dto): void
    {
        $role = SupplierRole::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $supplierId,
            'name' => $dto->name,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        foreach ($dto->permissions as $permission) {
            SupplierRolePermission::create([
                'id' => Str::uuid()->toString(),
                'supplier_role_id' => $role->id,
                'permission' => $permission,
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
            ]);
        }
    }

    /**
     * Update an existing supplier role and its permissions
     *
     * @param string $roleId
     * @param string $supplierId
     * @param SupplierRoleRequestDto $dto
     * @return void
     */
    public function updateRole(string $roleId, string $supplierId, SupplierRoleRequestDto $dto): void
    {
        $role = SupplierRole::where('id', $roleId)
            ->where('supplier_id', $supplierId)
            ->firstOrFail();

        $role->update([
            'name' => $dto->name,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        // Delete existing permissions
        SupplierRolePermission::where('supplier_role_id', $role->id)->delete();

        // Create new permissions
        foreach ($dto->permissions as $permission) {
            SupplierRolePermission::create([
                'id' => Str::uuid()->toString(),
                'supplier_role_id' => $role->id,
                'permission' => $permission,
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
            ]);
        }
    }

    /**
     * Delete a supplier role
     *
     * @param string $roleId
     * @param string $supplierId
     * @return void
     */
    public function deleteRole(string $roleId, string $supplierId): void
    {
        $role = SupplierRole::where('id', $roleId)
            ->where('supplier_id', $supplierId)
            ->firstOrFail();

        // Soft delete associated permissions
        SupplierRolePermission::where('supplier_role_id', $role->id)->delete();

        // Soft delete the role
        $role->delete();
    }
}
