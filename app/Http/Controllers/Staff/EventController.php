<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\CreateEventRequestDto;
use App\Dto\Request\GetEventsRequestDto;
use App\Dto\Request\UpdateEventRequestDto;
use App\Dto\Response\EventResponseDto;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\GetEventsRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\Staff\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(GetEventsRequest $request, EventService $eventService): JsonResponse
    {
        $dto = GetEventsRequestDto::fromArray($request->validated());

        $events = $eventService->getEvents($dto);

        $response = $events->map(fn($eventData) => EventResponseDto::fromEventData($eventData)->toArray());

        return response()->json($response, 200);
    }

    public function store(CreateEventRequest $request, EventService $eventService): JsonResponse
    {
        $dto = CreateEventRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($dto, $eventService) {
            $eventService->createEvent($dto);
        });

        return response()->json(null, 201);
    }

    public function update(UpdateEventRequest $request, string $id, EventService $eventService): JsonResponse
    {
        $dto = UpdateEventRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($id, $dto, $eventService) {
            $eventService->updateEvent($id, $dto);
        });

        return response()->json(null, 200);
    }
}
