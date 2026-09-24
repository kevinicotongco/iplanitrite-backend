<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\SortRequestDto;
use App\Http\Requests\CreateAccountTemplateChecklistRequest;
use App\Http\Requests\UpdateAccountTemplateChecklistFrequencyRequest;
use App\Http\Requests\UpdateAccountTemplateChecklistNameRequest;
use App\Http\Requests\UpdateAccountTemplateChecklistResponsibilityRequest;
use App\Http\Requests\UpdateAccountTemplateChecklistSupplierRequest;
use App\Services\Staff\AccountTemplateChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AccountTemplateChecklistController extends Controller
{
    /**
     * Create checklist
     */
    public function store(string $groupId, CreateAccountTemplateChecklistRequest $request, AccountTemplateChecklistService $checklistService): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($groupId, $dto, $checklistService) {
            $checklistService->createChecklist($groupId, $dto->name);
        });

        return response()->json([], 200);
    }

    /**
     * Update checklist name
     */
    public function updateName(string $groupId, string $checklistId, UpdateAccountTemplateChecklistNameRequest $request, AccountTemplateChecklistService $checklistService): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($groupId, $checklistId, $dto, $checklistService) {
            $checklistService->updateChecklistName($groupId, $checklistId, $dto->name);
        });

        return response()->json([], 200);
    }

    /**
     * Update checklist frequency
     */
    public function updateFrequency(string $groupId, string $checklistId, UpdateAccountTemplateChecklistFrequencyRequest $request, AccountTemplateChecklistService $checklistService): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($groupId, $checklistId, $dto, $checklistService) {
            $checklistService->updateChecklistFrequency(
                $groupId,
                $checklistId,
                $dto->frequencyType,
                $dto->frequencyAnchor,
                $dto->frequencyValue
            );
        });

        return response()->json([], 200);
    }

    /**
     * Update checklist responsibility
     */
    public function updateResponsibility(string $groupId, string $checklistId, UpdateAccountTemplateChecklistResponsibilityRequest $request, AccountTemplateChecklistService $checklistService): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($groupId, $checklistId, $dto, $checklistService) {
            $checklistService->updateChecklistResponsibility(
                $groupId,
                $checklistId,
                $dto->responsibilityType
            );
        });

        return response()->json([], 200);
    }

    /**
     * Update checklist supplier
     */
    public function updateSupplier(string $groupId, string $checklistId, UpdateAccountTemplateChecklistSupplierRequest $request, AccountTemplateChecklistService $checklistService): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($groupId, $checklistId, $dto, $checklistService) {
            $checklistService->updateChecklistSupplier(
                $groupId,
                $checklistId,
                $dto->supplierId
            );
        });

        return response()->json([], 200);
    }

    /**
     * Bulk update checklist sort order
     */
    public function updateSort(string $groupId, Request $request, AccountTemplateChecklistService $checklistService): JsonResponse
    {
        $sortData = array_map(
            fn($item) => SortRequestDto::fromArray($item),
            $request->input()
        );

        $sortDataArray = array_map(
            fn(SortRequestDto $dto) => ['id' => $dto->id, 'sortOrder' => $dto->sortOrder],
            $sortData
        );

        DB::transaction(function () use ($groupId, $sortDataArray, $checklistService) {
            $checklistService->updateChecklistsSortOrder($groupId, $sortDataArray);
        });

        return response()->json([], 200);
    }

    /**
     * Delete checklist
     */
    public function destroy(string $groupId, string $checklistId, AccountTemplateChecklistService $checklistService): JsonResponse
    {
        DB::transaction(function () use ($groupId, $checklistId, $checklistService) {
            $checklistService->deleteChecklist($groupId, $checklistId);
        });

        return response()->json([], 200);
    }
}
