<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Dto\Response\EventChecklistGroupsByTypeResponseDto;
use App\Services\Client\EventChecklistGroupService;
use App\Services\Client\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class EventChecklistGroupController extends Controller
{
    public function index(
        string $eventId,
        EventService $eventService,
        EventChecklistGroupService $groupService
    ): JsonResponse {
        $event = $eventService->getEventForClient($eventId);

        $groupsData = $groupService->getGroupsByType($event);

        return response()->json(EventChecklistGroupsByTypeResponseDto::fromData($groupsData)->toArray(), 200);
    }
}
