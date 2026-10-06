<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\EventFloorPlanResponseDto;
use App\Http\Requests\UpdateEventFloorPlanRequest;
use App\Services\Staff\EventFloorPlanService;
use App\Services\Staff\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventFloorPlanController extends Controller
{
    public function show(
        string $eventId,
        EventService $eventService,
        EventFloorPlanService $eventFloorPlanService
    ): JsonResponse {
        $event = $eventService->getEventForAccount($eventId);

        $data = $eventFloorPlanService->getFloorPlanWithSeats($event);

        return response()->json(EventFloorPlanResponseDto::fromData($data)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function update(
        string $eventId,
        string $floorPlanId,
        UpdateEventFloorPlanRequest $request,
        EventService $eventService,
        EventFloorPlanService $eventFloorPlanService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($eventId, $floorPlanId, $dto, $eventService, $eventFloorPlanService): void {
            $event = $eventService->getEventForAccount($eventId);

            $eventFloorPlanService->updateCanvas($event, $floorPlanId, $dto->canvas);
        });

        return response()->json((object) [], 200);
    }
}
