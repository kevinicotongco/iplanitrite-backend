<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Dto\Response\EventFloorPlanResponseDto;
use App\Services\Client\EventFloorPlanService;
use App\Services\Client\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class EventFloorPlanController extends Controller
{
    public function show(
        string $eventId,
        EventService $eventService,
        EventFloorPlanService $eventFloorPlanService
    ): JsonResponse {
        $event = $eventService->getEventForClient($eventId);

        $data = $eventFloorPlanService->getFloorPlanWithSeats($event);

        return response()->json(EventFloorPlanResponseDto::fromData($data)->toArray(), 200);
    }
}
