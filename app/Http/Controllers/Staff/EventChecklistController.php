<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Data\EventChecklistAssigneeData;
use App\Dto\Response\EventChecklistUpdateAssigneeResponseDto;
use App\Dto\Response\SupplierResponseDto;
use App\Http\Requests\CreateEventChecklistRequest;
use App\Http\Requests\SortRequest;
use App\Http\Requests\UpdateEventChecklistAssigneesRequest;
use App\Http\Requests\UpdateEventChecklistDueDateRequest;
use App\Http\Requests\UpdateEventChecklistNameRequest;
use App\Http\Requests\UpdateEventChecklistStatusRequest;
use App\Http\Requests\UpdateEventChecklistSupplierRequest;
use App\Models\Supplier;
use App\Services\Staff\EventChecklistGroupService;
use App\Services\Staff\EventChecklistService;
use App\Services\Staff\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventChecklistController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(
        string $id,
        string $groupId,
        CreateEventChecklistRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($id, $groupId, $dto, $eventService, $groupService, $checklistService): void {
            $group = $groupService->getGroupForEvent($eventService->getEventForAccount($id), $groupId);
            $checklistService->createChecklist($group, $dto->name);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateSort(
        string $id,
        string $groupId,
        SortRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $sortDtos = $request->toDtos();

        DB::transaction(function () use ($id, $groupId, $sortDtos, $eventService, $groupService, $checklistService): void {
            $group = $groupService->getGroupForEvent($eventService->getEventForAccount($id), $groupId);
            $checklistService->sortChecklists($group, $sortDtos);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateName(
        string $id,
        string $groupId,
        string $checklistId,
        UpdateEventChecklistNameRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($id, $groupId, $checklistId, $dto, $eventService, $groupService, $checklistService): void {
            $group = $groupService->getGroupForEvent($eventService->getEventForAccount($id), $groupId);
            $checklistService->updateChecklistName($group, $checklistId, $dto->name);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateDueDate(
        string $id,
        string $groupId,
        string $checklistId,
        UpdateEventChecklistDueDateRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($id, $groupId, $checklistId, $dto, $eventService, $groupService, $checklistService): void {
            $group = $groupService->getGroupForEvent($eventService->getEventForAccount($id), $groupId);
            $checklistService->updateChecklistDueDate($group, $checklistId, $dto->dueDate);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateStatus(
        string $id,
        string $groupId,
        string $checklistId,
        UpdateEventChecklistStatusRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($id, $groupId, $checklistId, $dto, $eventService, $groupService, $checklistService): void {
            $group = $groupService->getGroupForEvent($eventService->getEventForAccount($id), $groupId);
            $checklistService->updateChecklistStatus($group, $checklistId, $dto->status);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateAssignees(
        string $id,
        string $groupId,
        string $checklistId,
        UpdateEventChecklistAssigneesRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $assigneeDtos = $request->toDtos();

        /** @var Collection<int, EventChecklistAssigneeData> $assignees */
        $assignees = DB::transaction(function () use ($id, $groupId, $checklistId, $assigneeDtos, $eventService, $groupService, $checklistService): Collection {
            $event = $eventService->getEventForAccount($id);
            $group = $groupService->getGroupForEvent($event, $groupId);

            return $checklistService->updateChecklistAssignees($event, $group, $checklistId, $assigneeDtos);
        });

        $response = $assignees->map(
            fn(EventChecklistAssigneeData $assigneeData): array => EventChecklistUpdateAssigneeResponseDto::fromData($assigneeData)->toArray()
        );

        return response()->json($response->all(), 200);
    }

    /**
     * @throws Throwable
     */
    public function updateSupplier(
        string $id,
        string $groupId,
        string $checklistId,
        UpdateEventChecklistSupplierRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var Supplier $supplier */
        $supplier = DB::transaction(function () use ($id, $groupId, $checklistId, $dto, $eventService, $groupService, $checklistService): Supplier {
            $group = $groupService->getGroupForEvent($eventService->getEventForAccount($id), $groupId);

            return $checklistService->updateChecklistSupplier($group, $checklistId, $dto->supplierId);
        });

        return response()->json(SupplierResponseDto::fromModel($supplier)->toArray(), 200);
    }
}
