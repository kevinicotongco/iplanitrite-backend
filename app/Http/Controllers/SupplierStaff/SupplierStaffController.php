<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupplierStaff;

use App\Dto\Request\ManageSupplierStaffRequestDto;
use App\Dto\Response\SupplierStaffResponseDto;
use App\Http\Requests\CreateStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Services\SupplierStaff\AddressService;
use App\Services\SupplierStaff\ContactNumberService;
use App\Services\SupplierStaff\SupplierStaffManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SupplierStaffController extends Controller
{
    public function __construct() {}

    /**
     * Get all staff for the authenticated supplier staff's supplier
     */
    public function index(): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;

        $service = $this->createService($staff);
        $staffList = $service->getStaff($supplierId);

        $response = $staffList->map(fn($s) => SupplierStaffResponseDto::fromModel($s)->toArray());

        return response()->json($response, 200);
    }

    /**
     * Create a new supplier staff member
     */
    public function store(CreateStaffRequest $request): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;
        $supplier = $staff->supplier;
        $countryId = $supplier->country_id;

        $dto = ManageSupplierStaffRequestDto::fromArray($request->validated());

        $service = $this->createService($staff);

        DB::transaction(function () use ($service, $supplierId, $countryId, $dto) {
            $service->createStaff($supplierId, $countryId, $dto);
        });

        return response()->json(null, 201);
    }

    /**
     * Update an existing supplier staff member
     */
    public function update(UpdateStaffRequest $request, string $id): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;
        $supplier = $staff->supplier;
        $countryId = $supplier->country_id;

        $dto = ManageSupplierStaffRequestDto::fromArray($request->validated());

        $service = $this->createService($staff);

        DB::transaction(function () use ($service, $id, $supplierId, $countryId, $dto) {
            $service->updateStaff($id, $supplierId, $countryId, $dto);
        });

        return response()->json(null, 200);
    }

    /**
     * Delete a supplier staff member
     */
    public function destroy(string $id): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;

        $service = $this->createService($staff);

        DB::transaction(function () use ($service, $id, $supplierId) {
            $service->deleteStaff($id, $supplierId);
        });

        return response()->json(null, 204);
    }

    /**
     * Create SupplierStaffManagementService with dependencies
     */
    private function createService($staff): SupplierStaffManagementService
    {
        $addressService = app(AddressService::class, ['authenticatedUser' => $staff]);
        $contactNumberService = app(ContactNumberService::class, ['authenticatedUser' => $staff]);

        return app(SupplierStaffManagementService::class, [
            'authenticatedUser' => $staff,
            'addressService' => $addressService,
            'contactNumberService' => $contactNumberService,
        ]);
    }
}
