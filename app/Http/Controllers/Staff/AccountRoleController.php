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
    public function __construct() {}

    /**
     * Get all roles for the authenticated staff's account
     */
    public function index(): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;

        $service = app(AccountRoleService::class, ['authenticatedUser' => $staff]);
        $roles = $service->getRoles($accountId);

        $response = $roles->map(fn($role) => AccountRoleResponseDto::fromData($role)->toArray());

        return response()->json($response, 200);
    }

    /**
     * Create a new account role
     */
    public function store(CreateAccountRoleRequest $request): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;

        $dto = AccountRoleRequestDto::fromArray($request->validated());

        $service = app(AccountRoleService::class, ['authenticatedUser' => $staff]);

        DB::transaction(function () use ($service, $accountId, $dto) {
            $service->createRole($accountId, $dto);
        });

        return response()->json(null, 201);
    }

    /**
     * Update an existing account role
     */
    public function update(UpdateAccountRoleRequest $request, string $id): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;

        $dto = AccountRoleRequestDto::fromArray($request->validated());

        $service = app(AccountRoleService::class, ['authenticatedUser' => $staff]);

        DB::transaction(function () use ($service, $id, $accountId, $dto) {
            $service->updateRole($id, $accountId, $dto);
        });

        return response()->json(null, 200);
    }

    /**
     * Delete a account role
     */
    public function destroy(string $id): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;

        $service = app(AccountRoleService::class, ['authenticatedUser' => $staff]);

        DB::transaction(function () use ($service, $id, $accountId) {
            $service->deleteRole($id, $accountId);
        });

        return response()->json(null, 204);
    }
}
