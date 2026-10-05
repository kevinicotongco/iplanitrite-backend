<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Data\InvoiceWithBalanceData;
use App\Dto\Response\InvoiceResponseDto;
use App\Dto\Response\InvoiceShowResponseDto;
use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\UpdateInvoiceDueDateRequest;
use App\Services\Staff\EventInvoiceService;
use App\Services\Staff\EventService;
use App\Services\Staff\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvoiceController extends Controller
{
    public function index(
        string $eventId,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService
    ): JsonResponse {
        $event = $eventService->getEventForAccount($eventId);

        $response = $eventInvoiceService->getInvoicesForEvent($event)->map(
            fn(InvoiceWithBalanceData $data): array => InvoiceResponseDto::fromData($data)->toArray()
        );

        return response()->json($response->values()->toArray(), 200);
    }

    public function show(
        string $eventId,
        string $invoiceId,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoiceService $invoiceService
    ): JsonResponse {
        $event = $eventService->getEventForAccount($eventId);
        $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);

        return response()->json(
            InvoiceShowResponseDto::fromData($invoiceService->getInvoiceShow($invoice))->toArray(),
            200
        );
    }

    /**
     * @throws Throwable
     */
    public function store(
        string $eventId,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService
    ): JsonResponse {
        DB::transaction(function () use ($eventId, $eventService, $eventInvoiceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $eventInvoiceService->createInvoiceForEvent($event);
        });

        return response()->json((object) [], 201);
    }

    /**
     * @throws Throwable
     */
    public function updateDueDate(
        string $eventId,
        string $invoiceId,
        UpdateInvoiceDueDateRequest $request,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoiceService $invoiceService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($eventId, $invoiceId, $dto, $eventService, $eventInvoiceService, $invoiceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);
            $invoiceService->updateDueDate($invoice, $dto);
        });

        return response()->json((object) [], 200);
    }

    /**
     * @throws Throwable
     */
    public function markReady(
        string $eventId,
        string $invoiceId,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoiceService $invoiceService
    ): JsonResponse {
        DB::transaction(function () use ($eventId, $invoiceId, $eventService, $eventInvoiceService, $invoiceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);
            $invoiceService->markReady($invoice);
        });

        return response()->json((object) [], 200);
    }

    /**
     * @throws Throwable
     */
    public function markPending(
        string $eventId,
        string $invoiceId,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoiceService $invoiceService
    ): JsonResponse {
        DB::transaction(function () use ($eventId, $invoiceId, $eventService, $eventInvoiceService, $invoiceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);
            $invoiceService->markPending($invoice);
        });

        return response()->json((object) [], 200);
    }

    /**
     * @throws Throwable
     */
    public function cancel(
        string $eventId,
        string $invoiceId,
        CancelInvoiceRequest $request,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoiceService $invoiceService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($eventId, $invoiceId, $dto, $eventService, $eventInvoiceService, $invoiceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);
            $invoiceService->cancel($invoice, $dto);
        });

        return response()->json((object) [], 200);
    }
}
