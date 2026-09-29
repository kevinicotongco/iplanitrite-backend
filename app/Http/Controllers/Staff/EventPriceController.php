<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\EventPriceResponseDto;
use App\Http\Requests\EventPriceRequest;
use App\Http\Requests\SortRequest;
use App\Models\EventPrice;
use App\Services\Staff\EventPriceService;
use App\Services\Staff\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventPriceController extends Controller
{
    public function index(string $eventId, EventService $eventService, EventPriceService $eventPriceService): JsonResponse
    {
        $event = $eventService->getEventForAccount($eventId);

        $response = $eventPriceService->getPricesForEvent($event)->map(
            fn(EventPrice $eventPrice): array => EventPriceResponseDto::fromModel($eventPrice)->toArray()
        );

        return response()->json($response->values()->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function store(
        string $eventId,
        EventPriceRequest $request,
        EventService $eventService,
        EventPriceService $eventPriceService
    ): JsonResponse {
        $dto = $request->toDto();

        $eventPrice = DB::transaction(function () use ($eventId, $dto, $eventService, $eventPriceService): EventPrice {
            $event = $eventService->getEventForAccount($eventId);

            return $eventPriceService->createPrice($event, $dto);
        });

        return response()->json(EventPriceResponseDto::fromModel($eventPrice)->toArray(), 201);
    }

    /**
     * @throws Throwable
     */
    public function update(
        string $eventId,
        string $eventPriceId,
        EventPriceRequest $request,
        EventService $eventService,
        EventPriceService $eventPriceService
    ): JsonResponse {
        $dto = $request->toDto();

        $eventPrice = DB::transaction(function () use ($eventId, $eventPriceId, $dto, $eventService, $eventPriceService): EventPrice {
            $event = $eventService->getEventForAccount($eventId);

            return $eventPriceService->updatePrice($event, $eventPriceId, $dto);
        });

        return response()->json(EventPriceResponseDto::fromModel($eventPrice)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function updateSort(
        string $eventId,
        SortRequest $request,
        EventService $eventService,
        EventPriceService $eventPriceService
    ): JsonResponse {
        $sortDtos = $request->toDtos();

        DB::transaction(function () use ($eventId, $sortDtos, $eventService, $eventPriceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $eventPriceService->sortPrices($event, $sortDtos);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function destroy(
        string $eventId,
        string $eventPriceId,
        EventService $eventService,
        EventPriceService $eventPriceService
    ): JsonResponse {
        DB::transaction(function () use ($eventId, $eventPriceId, $eventService, $eventPriceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $eventPriceService->deletePrice($event, $eventPriceId);
        });

        return response()->json(null, 200);
    }
}
