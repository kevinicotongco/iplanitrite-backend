<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\ManageStaffRequestDto;
use App\Dto\Response\StaffResponseDto;
use App\Http\Requests\CreateStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Services\Staff\StaffManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class StaffController extends Controller
{
    /**
     * Get all staff for the authenticated account staff's
     */
    public function index(StaffManagementService $staffManagementService): JsonResponse
    {
        $staffList = $staffManagementService->getStaff();

        $response = $staffList->map(fn($s) => StaffResponseDto::fromData($s)->toArray());

        return response()->json($response, 200);
    }

    /**
     * @throws Throwable
     */
    public function store(CreateStaffRequest $request, StaffManagementService $staffManagementService): JsonResponse
    {
        $dto = ManageStaffRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($dto, $staffManagementService) {
            $staffManagementService->createStaff($dto);
        });

        return response()->json(null, 201);
    }

    /**
     * Update an existing account staff member
     * @throws Throwable
     */
    public function update(UpdateStaffRequest $request, string $id, StaffManagementService $staffManagementService): JsonResponse
    {
        $dto = ManageStaffRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($id, $dto, $staffManagementService) {
            $staffManagementService->updateStaff($id, $dto);
        });

        return response()->json(null, 200);
    }

    /**
     * Delete a account staff member
     * @throws Throwable
     */
    public function destroy(string $id, StaffManagementService $staffManagementService): JsonResponse
    {
        DB::transaction(function () use ($id, $staffManagementService) {
            $staffManagementService->deleteStaff($id);
        });

        return response()->json(null, 204);
    }
}
