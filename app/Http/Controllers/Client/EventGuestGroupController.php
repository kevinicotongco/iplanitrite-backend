<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Data\EventGuestGroupWithGuestsData;
use App\Dto\Response\EventGuestGroupResponseDto;
use App\Http\Requests\CreateEventGuestGroupRequest;
use App\Http\Requests\SortRequest;
use App\Http\Requests\UpdateEventGuestGroupRequest;
use App\Models\EventGuestGroup;
use App\Services\Client\EventGuestGroupService;
use App\Services\Client\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventGuestGroupController extends Controller
{
    public function index(
        string $eventId,
        EventService $eventService,
        EventGuestGroupService $groupService
    ): JsonResponse {
        $event = $eventService->getEventForClient($eventId);

        $groups = $groupService->getGroupsWithGuests($event);

        return response()->json(
            $groups->map(fn(EventGuestGroupWithGuestsData $data): array => EventGuestGroupResponseDto::fromData($data)->toArray())->all(),
            200
        );
    }

    /**
     * @throws Throwable
     */
    public function store(
        string $eventId,
        CreateEventGuestGroupRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var EventGuestGroup $group */
        $group = DB::transaction(function () use ($eventId, $dto, $eventService, $groupService): EventGuestGroup {
            $event = $eventService->getEventForClient($eventId);

            return $groupService->createGroup($event, $dto);
        });

        return response()->json(EventGuestGroupResponseDto::fromModel($group)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function update(
        string $eventId,
        string $eventGuestGroupId,
        UpdateEventGuestGroupRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var EventGuestGroup $group */
        $group = DB::transaction(function () use ($eventId, $eventGuestGroupId, $dto, $eventService, $groupService): EventGuestGroup {
            $event = $eventService->getEventForClient($eventId);

            return $groupService->updateGroup($event, $eventGuestGroupId, $dto);
        });

        return response()->json(EventGuestGroupResponseDto::fromModel($group)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function updateSort(
        string $eventId,
        SortRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService
    ): JsonResponse {
        $sortDtos = $request->toDtos();

        DB::transaction(function () use ($eventId, $sortDtos, $eventService, $groupService): void {
            $event = $eventService->getEventForClient($eventId);
            $groupService->sortGroups($event, $sortDtos);
        });

        return response()->json(null, 200);
    }
}
