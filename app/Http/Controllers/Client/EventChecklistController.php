<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Dto\Response\SupplierResponseDto;
use App\Http\Requests\UpdateClientEventChecklistSupplierRequest;
use App\Http\Requests\UpdateEventChecklistStatusRequest;
use App\Models\Supplier;
use App\Services\Client\EventChecklistService;
use App\Services\Client\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventChecklistController extends Controller
{
    /**
     * @throws Throwable
     */
    public function updateStatus(
        string $eventId,
        string $checklistId,
        UpdateEventChecklistStatusRequest $request,
        EventService $eventService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($eventId, $checklistId, $dto, $eventService, $checklistService): void {
            $event = $eventService->getEventForClient($eventId);
            $checklistService->updateChecklistStatus($event, $checklistId, $dto->status);
        });

        return response()->json(null, 200);
    }

    /**
     * @throws Throwable
     */
    public function updateSupplier(
        string $eventId,
        string $checklistId,
        UpdateClientEventChecklistSupplierRequest $request,
        EventService $eventService,
        EventChecklistService $checklistService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var Supplier|null $supplier */
        $supplier = DB::transaction(function () use ($eventId, $checklistId, $dto, $eventService, $checklistService): ?Supplier {
            $event = $eventService->getEventForClient($eventId);

            return $checklistService->updateChecklistSupplier($event, $checklistId, $dto->supplierId);
        });

        return response()->json($supplier !== null ? SupplierResponseDto::fromModel($supplier)->toArray() : null, 200);
    }
}
