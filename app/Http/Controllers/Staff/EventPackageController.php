<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\EventPackageResponseDto;
use App\Enums\EventTypeEnum;
use App\Http\Requests\EventPackageRequest;
use App\Models\EventPackage;
use App\Services\Staff\EventPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class EventPackageController extends Controller
{
    public function index(EventTypeEnum $eventType, EventPackageService $eventPackageService): JsonResponse
    {
        $eventPackages = $eventPackageService->getPackagesByEventType($eventType);

        $response = $eventPackages->map(
            fn(EventPackage $eventPackage): array => EventPackageResponseDto::fromModel($eventPackage)->toArray()
        );

        return response()->json($response->values()->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function store(
        EventTypeEnum $eventType,
        EventPackageRequest $request,
        EventPackageService $eventPackageService
    ): JsonResponse {
        $dto = $request->toDto();

        $eventPackage = DB::transaction(
            fn(): EventPackage => $eventPackageService->createPackage($eventType, $dto)
        );

        return response()->json(EventPackageResponseDto::fromModel($eventPackage)->toArray(), 201);
    }

    /**
     * @throws Throwable
     */
    public function update(
        EventTypeEnum $eventType,
        string $eventPackageId,
        EventPackageRequest $request,
        EventPackageService $eventPackageService
    ): JsonResponse {
        $dto = $request->toDto();

        $eventPackage = DB::transaction(
            fn(): EventPackage => $eventPackageService->updatePackage($eventType, $eventPackageId, $dto)
        );

        return response()->json(EventPackageResponseDto::fromModel($eventPackage)->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function destroy(
        EventTypeEnum $eventType,
        string $eventPackageId,
        EventPackageService $eventPackageService
    ): JsonResponse {
        DB::transaction(function () use ($eventType, $eventPackageId, $eventPackageService): void {
            $eventPackageService->deletePackage($eventType, $eventPackageId);
        });

        return response()->json(null, 200);
    }
}
