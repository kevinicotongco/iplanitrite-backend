<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\ManageStaffRequestDto;
use App\Dto\Response\StaffResponseDto;
use App\Http\Requests\CreateStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Services\Staff\AddressService;
use App\Services\Staff\ContactNumberService;
use App\Services\Staff\StaffManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class StaffController extends Controller
{
    public function __construct() {}

    /**
     * Get all staff for the authenticated account staff's
     */
    public function index(): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;

        $service = $this->createService($staff);
        $staffList = $service->getStaff($accountId);

        $response = $staffList->map(fn($s) => StaffResponseDto::fromData($s)->toArray());

        return response()->json($response, 200);
    }

    /**
     * @throws Throwable
     */
    public function store(CreateStaffRequest $request): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;
        $account = $staff->account;
        $countryId = $account->country_id;

        $dto = ManageStaffRequestDto::fromArray($request->validated());

        $service = $this->createService($staff);

        DB::transaction(function () use ($service, $accountId, $countryId, $dto) {
            $service->createStaff($accountId, $countryId, $dto);
        });

        return response()->json(null, 201);
    }

    /**
     * Update an existing account staff member
     * @throws Throwable
     */
    public function update(UpdateStaffRequest $request, string $id): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;
        $account = $staff->account;
        $countryId = $account->country_id;

        $dto = ManageStaffRequestDto::fromArray($request->validated());

        $service = $this->createService($staff);

        DB::transaction(function () use ($service, $id, $accountId, $countryId, $dto) {
            $service->updateStaff($id, $accountId, $countryId, $dto);
        });

        return response()->json(null, 200);
    }

    /**
     * Delete a account staff member
     * @throws Throwable
     */
    public function destroy(string $id): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;

        $service = $this->createService($staff);

        DB::transaction(function () use ($service, $id, $accountId) {
            $service->deleteStaff($id, $accountId);
        });

        return response()->json(null, 204);
    }

    /**
     * Create StaffManagementService with dependencies
     */
    private function createService($staff): StaffManagementService
    {
        $addressService = app(AddressService::class, ['authenticatedUser' => $staff]);
        $contactNumberService = app(ContactNumberService::class, ['authenticatedUser' => $staff]);

        return app(StaffManagementService::class, [
            'authenticatedUser' => $staff,
            'addressService' => $addressService,
            'contactNumberService' => $contactNumberService,
        ]);
    }
}
