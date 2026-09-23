<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupplierStaff;

use App\Dto\Request\SupplierRoleRequestDto;
use App\Dto\Response\SupplierRoleResponseDto;
use App\Http\Requests\CreateSupplierRoleRequest;
use App\Http\Requests\UpdateSupplierRoleRequest;
use App\Services\SupplierStaff\SupplierRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SupplierStaffRoleController extends Controller
{
    public function __construct() {}

    /**
     * Get all roles for the authenticated supplier staff's supplier
     */
    public function index(): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;

        $service = app(SupplierRoleService::class, ['authenticatedUser' => $staff]);
        $roles = $service->getRoles($supplierId);

        $response = $roles->map(fn($role) => SupplierRoleResponseDto::fromData($role)->toArray());

        return response()->json($response, 200);
    }

    /**
     * Create a new supplier role
     */
    public function store(CreateSupplierRoleRequest $request): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;

        $dto = SupplierRoleRequestDto::fromArray($request->validated());

        $service = app(SupplierRoleService::class, ['authenticatedUser' => $staff]);

        DB::transaction(function () use ($service, $supplierId, $dto) {
            $service->createRole($supplierId, $dto);
        });

        return response()->json(null, 201);
    }

    /**
     * Update an existing supplier role
     */
    public function update(UpdateSupplierRoleRequest $request, string $id): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;

        $dto = SupplierRoleRequestDto::fromArray($request->validated());

        $service = app(SupplierRoleService::class, ['authenticatedUser' => $staff]);

        DB::transaction(function () use ($service, $id, $supplierId, $dto) {
            $service->updateRole($id, $supplierId, $dto);
        });

        return response()->json(null, 200);
    }

    /**
     * Delete a supplier role
     */
    public function destroy(string $id): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;

        $service = app(SupplierRoleService::class, ['authenticatedUser' => $staff]);

        DB::transaction(function () use ($service, $id, $supplierId) {
            $service->deleteRole($id, $supplierId);
        });

        return response()->json(null, 204);
    }
}
