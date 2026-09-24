<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\AccountRoleRequestDto;
use App\Dto\Response\AccountRoleResponseDto;
use App\Http\Requests\CreateAccountRoleRequest;
use App\Http\Requests\UpdateAccountRoleRequest;
use App\Services\Staff\AccountRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AccountRoleController extends Controller
{
    /**
     * Get all roles for the authenticated staff's account
     */
    public function index(AccountRoleService $accountRoleService): JsonResponse
    {
        $roles = $accountRoleService->getRoles();

        $response = $roles->map(fn($role) => AccountRoleResponseDto::fromData($role)->toArray());

        return response()->json($response, 200);
    }

    /**
     * Create a new account role
     */
    public function store(CreateAccountRoleRequest $request, AccountRoleService $accountRoleService): JsonResponse
    {
        $dto = AccountRoleRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($dto, $accountRoleService) {
            $accountRoleService->createRole($dto);
        });

        return response()->json(null, 201);
    }

    /**
     * Update an existing account role
     */
    public function update(UpdateAccountRoleRequest $request, string $id, AccountRoleService $accountRoleService): JsonResponse
    {
        $dto = AccountRoleRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($id, $dto, $accountRoleService) {
            $accountRoleService->updateRole($id, $dto);
        });

        return response()->json(null, 200);
    }

    /**
     * Delete a account role
     */
    public function destroy(string $id, AccountRoleService $accountRoleService): JsonResponse
    {
        DB::transaction(function () use ($id, $accountRoleService) {
            $accountRoleService->deleteRole($id);
        });

        return response()->json(null, 204);
    }
}
