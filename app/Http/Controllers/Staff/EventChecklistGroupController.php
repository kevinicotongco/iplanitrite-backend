<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\EventChecklistGroupsByTypeResponseDto;
use App\Http\Requests\CreateEventChecklistGroupRequest;
use App\Http\Requests\SortRequest;
use App\Http\Requests\UpdateEventChecklistGroupNameRequest;
use App\Services\Staff\EventChecklistGroupService;
use App\Services\Staff\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventChecklistGroupController extends Controller
{
    public function index(string $id, EventService $eventService, EventChecklistGroupService $groupService): JsonResponse
    {
        $event = $eventService->getEventForAccount($id);

        $groupsData = $groupService->getGroupsByType($event);

        return response()->json(EventChecklistGroupsByTypeResponseDto::fromData($groupsData)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function store(
        string $id,
        CreateEventChecklistGroupRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($id, $dto, $eventService, $groupService): void {
            $event = $eventService->getEventForAccount($id);
            $groupService->createGroup($event, $dto);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateSort(
        string $id,
        SortRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService
    ): JsonResponse {
        $sortDtos = $request->toDtos();

        DB::transaction(function () use ($id, $sortDtos, $eventService, $groupService): void {
            $event = $eventService->getEventForAccount($id);
            $groupService->sortGroups($event, $sortDtos);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateName(
        string $id,
        string $groupId,
        UpdateEventChecklistGroupNameRequest $request,
        EventService $eventService,
        EventChecklistGroupService $groupService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($id, $groupId, $dto, $eventService, $groupService): void {
            $event = $eventService->getEventForAccount($id);
            $groupService->updateGroupName($event, $groupId, $dto->name);
        });

        return response()->json(null, 200);
    }
}
