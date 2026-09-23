<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\AccountRoleData;
use App\Dto\Request\AccountRoleRequestDto;
use App\Models\AccountRole;
use App\Models\AccountRolePermission;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class AccountRoleService
{
    public function __construct(
        private Staff $authenticatedUser,
    ) {}

    /**
     * @param string $accountId
     * @return Collection<AccountRoleData>
     */
    public function getRoles(string $accountId): Collection
    {
        $roles = AccountRole::where('account_id', $accountId)
            ->with('permissions')
            ->get();

        return $roles->map(fn($role) => AccountRoleData::fromModel($role));
    }

    /**
     * @param string $accountId
     * @param AccountRoleRequestDto $dto
     * @return void
     */
    public function createRole(string $accountId, AccountRoleRequestDto $dto): void
    {
        $role = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $accountId,
            'name' => $dto->name,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        foreach ($dto->permissions as $permission) {
            AccountRolePermission::create([
                'id' => Str::uuid()->toString(),
                'account_role_id' => $role->id,
                'permission' => $permission,
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
            ]);
        }
    }

    /**
     * @param string $roleId
     * @param string $accountId
     * @param AccountRoleRequestDto $dto
     * @return void
     */
    public function updateRole(string $roleId, string $accountId, AccountRoleRequestDto $dto): void
    {
        $role = AccountRole::where('id', $roleId)
            ->where('account_id', $accountId)
            ->firstOrFail();

        $role->update([
            'name' => $dto->name,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        // Delete existing permissions
        AccountRolePermission::where('account_role_id', $role->id)->delete();

        // Create new permissions
        foreach ($dto->permissions as $permission) {
            AccountRolePermission::create([
                'id' => Str::uuid()->toString(),
                'account_role_id' => $role->id,
                'permission' => $permission,
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
            ]);
        }
    }

    /**
     * @param string $roleId
     * @param string $accountId
     * @return void
     */
    public function deleteRole(string $roleId, string $accountId): void
    {
        $role = AccountRole::where('id', $roleId)
            ->where('account_id', $accountId)
            ->firstOrFail();

        // Soft delete associated permissions
        AccountRolePermission::where('account_role_id', $role->id)->delete();

        // Soft delete the role
        $role->delete();
    }
}
