<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\CreateEventRequestDto;
use App\Dto\Request\GetEventsRequestDto;
use App\Dto\Request\UpdateEventRequestDto;
use App\Dto\Response\AddressResponseDto;
use App\Dto\Response\ClientResponseDto;
use App\Dto\Response\DocumentResponseDto;
use App\Dto\Response\EventDashboardResponseDto;
use App\Dto\Response\EventDocumentGroupResponseDto;
use App\Dto\Response\EventResponseDto;
use App\Dto\Response\EventSegmentResponseDto;
use App\Dto\Response\GuestCountResponseDto;
use App\Dto\Response\CelebrantResponseDto;
use App\Enums\EventGuestStatusEnum;
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

    public function show(string $id, EventService $eventService): JsonResponse
    {
        $event = $eventService->getEventById($id);

        // Get primary segment address (first primary segment)
        $primarySegment = $event->primarySegments->first();
        $address = $primarySegment && $primarySegment->address
            ? AddressResponseDto::fromModel($primarySegment->address)
            : null;

        // Map clients
        $clients = $event->clients->map(fn($client) => ClientResponseDto::fromModel($client))->toArray();

        // Map document groups
        $documentGroups = $event->themeDocumentGroups->map(
            fn($group) => EventDocumentGroupResponseDto::fromModel($group)
        )->toArray();

        // Calculate guest counts
        $confirmedCount = 0;
        $pendingCount = 0;
        $declinedCount = 0;

        foreach ($event->guestGroups as $guestGroup) {
            foreach ($guestGroup->guests as $guest) {
                if ($guest->status === EventGuestStatusEnum::Confirmed) {
                    $confirmedCount++;
                } elseif ($guest->status === EventGuestStatusEnum::Pending) {
                    $pendingCount++;
                } elseif ($guest->status === EventGuestStatusEnum::Declined) {
                    $declinedCount++;
                }
            }
        }

        $guestCount = new GuestCountResponseDto(
            confirmed: $confirmedCount,
            pending: $pendingCount,
            declined: $declinedCount
        );

        $dashboard = new EventDashboardResponseDto(
            id: $event->id,
            name: $event->name,
            description: $event->description,
            dressCode: $event->dress_code,
            theme: $event->theme,
            address: $address,
            clients: $clients,
            thumbnail: $event->thumbnail ? DocumentResponseDto::fromModel($event->thumbnail) : null,
            documents: $documentGroups,
            status: $event->status->value,
            eventType: $event->event_type->value,
            celebrantOne: CelebrantResponseDto::fromModel($event->celebrantOne),
            celebrantTwo: $event->celebrantTwo ? CelebrantResponseDto::fromModel($event->celebrantTwo) : null,
            primarySegment: EventSegmentResponseDto::fromModel($primarySegment),
            guestsCount: $guestCount
        );

        return response()->json($dashboard->toArray(), 200);
    }

    /**
     * @throws \Throwable
     */
    public function store(CreateEventRequest $request, EventService $eventService): JsonResponse
    {
        $dto = CreateEventRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($dto, $eventService) {
            $eventService->createEvent($dto);
        });

        return response()->json(null, 201);
    }

    /**
     * @throws \Throwable
     */
    public function update(UpdateEventRequest $request, string $id, EventService $eventService): JsonResponse
    {
        $dto = UpdateEventRequestDto::fromArray($request->validated());

        DB::transaction(function () use ($id, $dto, $eventService) {
            $eventService->updateEvent($id, $dto);
        });

        return response()->json(null, 200);
    }
}
