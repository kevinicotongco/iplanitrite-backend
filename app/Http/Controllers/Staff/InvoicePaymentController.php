<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\InvoicePaymentResponseDto;
use App\Http\Requests\CreatePaymentRequest;
use App\Http\Requests\UpdateInvoicePaymentStatusRequest;
use App\Models\InvoicePayment;
use App\Services\Staff\EventInvoiceService;
use App\Services\Staff\EventService;
use App\Services\Staff\InvoicePaymentService;
use App\Services\Staff\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvoicePaymentController extends Controller
{
    public function index(
        string $eventId,
        string $invoiceId,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoicePaymentService $invoicePaymentService
    ): JsonResponse {
        $event = $eventService->getEventForAccount($eventId);
        $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);

        $response = $invoicePaymentService->getPaymentsForInvoice($invoice)->map(
            fn(InvoicePayment $payment): array => InvoicePaymentResponseDto::fromModel($payment)->toArray()
        );

        return response()->json($response->values()->toArray(), 200);
    }

    /**
     * @throws Throwable
     */
    public function store(
        string $eventId,
        string $invoiceId,
        CreatePaymentRequest $request,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoiceService $invoiceService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($eventId, $invoiceId, $dto, $eventService, $eventInvoiceService, $invoiceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);
            $invoiceService->addPayment($invoice, $dto);
        });

        return response()->json((object) [], 201);
    }

    /**
     * @throws Throwable
     */
    public function updateStatus(
        string $eventId,
        string $invoiceId,
        string $paymentId,
        UpdateInvoicePaymentStatusRequest $request,
        EventService $eventService,
        EventInvoiceService $eventInvoiceService,
        InvoiceService $invoiceService
    ): JsonResponse {
        $dto = $request->toDto();

        DB::transaction(function () use ($eventId, $invoiceId, $paymentId, $dto, $eventService, $eventInvoiceService, $invoiceService): void {
            $event = $eventService->getEventForAccount($eventId);
            $invoice = $eventInvoiceService->getInvoiceForEvent($event, $invoiceId);
            $invoiceService->reviewPayment($invoice, $paymentId, $dto);
        });

        return response()->json((object) [], 200);
    }
}
