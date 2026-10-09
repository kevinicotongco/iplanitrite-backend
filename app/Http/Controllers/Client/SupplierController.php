<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Dto\Response\SupplierResponseDto;
use App\Http\Requests\CreateSupplierRequest;
use App\Models\Supplier;
use App\Services\Client\EventService;
use App\Services\Client\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class SupplierController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(
        string $eventId,
        CreateSupplierRequest $request,
        EventService $eventService,
        SupplierService $supplierService
    ): JsonResponse {
        $dto = $request->toDto();

        /** @var Supplier $supplier */
        $supplier = DB::transaction(function () use ($eventId, $dto, $eventService, $supplierService): Supplier {
            $event = $eventService->getEventForClient($eventId);

            return $supplierService->createSupplier($event, $dto);
        });

        return response()->json(SupplierResponseDto::fromModel($supplier)->toArray(), 200);
    }
}
