<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\SupplierResponseDto;
use App\Http\Requests\CreateSupplierRequest;
use App\Models\Staff;
use App\Models\Supplier;
use App\Services\Staff\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Get all suppliers
     */
    public function index(SupplierService $supplierService): JsonResponse
    {
        $suppliers = $supplierService->getAllSuppliers();

        $responseDtos = $suppliers->map(function ($supplierData) {
            $supplier = Supplier::with(['contactNumber', 'address.country'])->findOrFail($supplierData->id);
            return SupplierResponseDto::fromModel($supplier);
        });

        return response()->json($responseDtos->map(fn($dto) => $dto->toArray())->toArray(), 200);
    }

    /**
     * Get single supplier
     */
    public function show(string $supplierId, SupplierService $supplierService): JsonResponse
    {
        $supplierData = $supplierService->getSupplierById($supplierId);
        $supplier = Supplier::with(['contactNumber', 'address.country'])->findOrFail($supplierData->id);

        $responseDto = SupplierResponseDto::fromModel($supplier);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Create supplier
     */
    public function store(CreateSupplierRequest $request, SupplierService $supplierService): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($dto, $supplierService) {
            $supplierService->createSupplier(
                $dto->companyName,
                $dto->contactPerson,
                $dto->contactNumber,
                $dto->address
            );
        });

        return response()->json([], 200);
    }

    /**
     * Update supplier
     */
    public function update(string $supplierId, CreateSupplierRequest $request, SupplierService $supplierService): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($supplierId, $dto, $supplierService) {
            $supplierService->updateSupplier(
                $supplierId,
                $dto->companyName,
                $dto->contactPerson,
                $dto->contactNumber,
                $dto->address
            );
        });

        return response()->json([], 200);
    }

    /**
     * Delete supplier
     */
    public function destroy(string $supplierId, SupplierService $supplierService): JsonResponse
    {
        DB::transaction(function () use ($supplierId, $supplierService) {
            $supplierService->deleteSupplier($supplierId);
        });

        return response()->json([], 200);
    }
}
