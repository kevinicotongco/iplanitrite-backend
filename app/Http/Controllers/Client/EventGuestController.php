<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Dto\Response\EventGuestResponseDto;
use App\Http\Requests\AssignEventGuestRequest;
use App\Http\Requests\CancelEventGuestRequest;
use App\Http\Requests\CreateEventGuestRequest;
use App\Http\Requests\SortRequest;
use App\Http\Requests\UpdateEventGuestRequest;
use App\Models\EventGuest;
use App\Services\Client\EventFloorPlanService;
use App\Services\Client\EventGuestGroupService;
use App\Services\Client\EventGuestService;
use App\Services\Client\EventSeatService;
use App\Services\Client\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventGuestController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(
        string $eventId,
        string $eventGuestGroupId,
        CreateEventGuestRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService,
        EventGuestService $guestService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var EventGuest $guest */
        $guest = DB::transaction(function () use ($eventId, $eventGuestGroupId, $dto, $eventService, $groupService, $guestService): EventGuest {
            $group = $groupService->getGroupForEvent($eventService->getEventForClient($eventId), $eventGuestGroupId);

            return $guestService->createGuest($group, $dto);
        });

        return response()->json(EventGuestResponseDto::fromModel($guest)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function update(
        string $eventId,
        string $eventGuestGroupId,
        string $guestId,
        UpdateEventGuestRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService,
        EventGuestService $guestService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var EventGuest $guest */
        $guest = DB::transaction(function () use ($eventId, $eventGuestGroupId, $guestId, $dto, $eventService, $groupService, $guestService): EventGuest {
            $event = $eventService->getEventForClient($eventId);
            $group = $groupService->getGroupForEvent($event, $eventGuestGroupId);
            $targetGroup = $groupService->getGroupForEventInput($event, $dto->eventGuestGroupId);

            return $guestService->updateGuest($group, $guestId, $dto, $targetGroup);
        });

        return response()->json(EventGuestResponseDto::fromModel($guest)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function cancel(
        string $eventId,
        string $eventGuestGroupId,
        string $guestId,
        CancelEventGuestRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService,
        EventGuestService $guestService,
        EventSeatService $seatService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var EventGuest $guest */
        $guest = DB::transaction(function () use ($eventId, $eventGuestGroupId, $guestId, $dto, $eventService, $groupService, $guestService, $seatService): EventGuest {
            $group = $groupService->getGroupForEvent($eventService->getEventForClient($eventId), $eventGuestGroupId);

            $guest = $guestService->cancelGuest($group, $guestId, $dto);
            $seatService->unassignGuestFromAllFloorPlans($guest);

            return $guest;
        });

        return response()->json(EventGuestResponseDto::fromModel($guest)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function updateSort(
        string $eventId,
        string $eventGuestGroupId,
        SortRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService,
        EventGuestService $guestService
    ): JsonResponse {
        $sortDtos = $request->toDtos();

        DB::transaction(function () use ($eventId, $eventGuestGroupId, $sortDtos, $eventService, $groupService, $guestService): void {
            $group = $groupService->getGroupForEvent($eventService->getEventForClient($eventId), $eventGuestGroupId);

            $guestService->sortGuests($group, $sortDtos);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function destroy(
        string $eventId,
        string $eventGuestGroupId,
        string $guestId,
        EventService $eventService,
        EventGuestGroupService $groupService,
        EventGuestService $guestService
    ): JsonResponse {
        DB::transaction(function () use ($eventId, $eventGuestGroupId, $guestId, $eventService, $groupService, $guestService): void {
            $group = $groupService->getGroupForEvent($eventService->getEventForClient($eventId), $eventGuestGroupId);

            $guestService->deleteGuest($group, $guestId);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function assign(
        string $eventId,
        string $eventGuestGroupId,
        string $guestId,
        AssignEventGuestRequest $request,
        EventService $eventService,
        EventGuestGroupService $groupService,
        EventGuestService $guestService,
        EventFloorPlanService $floorPlanService,
        EventSeatService $seatService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($eventId, $eventGuestGroupId, $guestId, $dto, $eventService, $groupService, $guestService, $floorPlanService, $seatService): void {
            $event = $eventService->getEventForClient($eventId);
            $group = $groupService->getGroupForEvent($event, $eventGuestGroupId);
            $guest = $guestService->getGuestForGroup($group, $guestId);
            $floorPlan = $floorPlanService->getFirstFloorPlanForEvent($event);

            $seatService->assignGuestToSeat($floorPlan, $guest, $dto->seatId);
        });

        return response()->json((object) [], 200);
    }

    /**
     * @throws Throwable
     */
    public function unassign(
        string $eventId,
        string $eventGuestGroupId,
        string $guestId,
        EventService $eventService,
        EventGuestGroupService $groupService,
        EventGuestService $guestService,
        EventFloorPlanService $floorPlanService,
        EventSeatService $seatService
    ): JsonResponse {
        DB::transaction(function () use ($eventId, $eventGuestGroupId, $guestId, $eventService, $groupService, $guestService, $floorPlanService, $seatService): void {
            $event = $eventService->getEventForClient($eventId);
            $group = $groupService->getGroupForEvent($event, $eventGuestGroupId);
            $guest = $guestService->getGuestForGroup($group, $guestId);
            $floorPlan = $floorPlanService->getFirstFloorPlanForEvent($event);

            $seatService->unassignGuest($floorPlan, $guest);
        });

        return response()->json((object) [], 200);
    }
}
