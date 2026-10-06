<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Requests\UpdateEventSeatsBySeatRequest;
use App\Http\Requests\UpdateEventSeatsByTableRequest;
use App\Services\Client\EventFloorPlanService;
use App\Services\Client\EventSeatService;
use App\Services\Client\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventSeatController extends Controller
{
    /**
     * @throws Throwable
     */
    public function updateByTable(
        string $eventId,
        string $floorPlanId,
        string $tableId,
        UpdateEventSeatsByTableRequest $request,
        EventService $eventService,
        EventFloorPlanService $eventFloorPlanService,
        EventSeatService $eventSeatService
    ): JsonResponse {
        $assignments = $request->toDtos();

        DB::transaction(function () use ($eventId, $floorPlanId, $tableId, $assignments, $eventService, $eventFloorPlanService, $eventSeatService): void {
            $event = $eventService->getEventForClient($eventId);
            $floorPlan = $eventFloorPlanService->getFloorPlanForEvent($event, $floorPlanId);

            $eventSeatService->assignGuestsByTable($floorPlan, $tableId, $assignments);
        });

        return response()->json((object) [], 200);
    }

    /**
     * @throws Throwable
     */
    public function updateBySeat(
        string $eventId,
        string $floorPlanId,
        string $tableId,
        UpdateEventSeatsBySeatRequest $request,
        EventService $eventService,
        EventFloorPlanService $eventFloorPlanService,
        EventSeatService $eventSeatService
    ): JsonResponse {
        $assignments = $request->toDtos();

        DB::transaction(function () use ($eventId, $floorPlanId, $tableId, $assignments, $eventService, $eventFloorPlanService, $eventSeatService): void {
            $event = $eventService->getEventForClient($eventId);
            $floorPlan = $eventFloorPlanService->getFloorPlanForEvent($event, $floorPlanId);

            $eventSeatService->assignGuestsBySeat($floorPlan, $tableId, $assignments);
        });

        return response()->json((object) [], 200);
    }
}
