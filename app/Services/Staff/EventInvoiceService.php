<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\InvoiceWithBalanceData;
use App\Models\Event;
use App\Models\EventInvoice;
use App\Models\Invoice;
use Illuminate\Support\Collection;

readonly class EventInvoiceService
{
    public function __construct(
        private EventInvoice $eventInvoiceModel,
        private InvoiceService $invoiceService,
    ) {}

    public function createInvoiceForEvent(Event $event): Invoice
    {
        $invoice = $this->invoiceService->createInvoice();

        $this->eventInvoiceModel::create([
            'event_id' => $event->id,
            'invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }

    /**
     * @return Collection<int, InvoiceWithBalanceData>
     */
    public function getInvoicesForEvent(Event $event): Collection
    {
        $invoiceIds = $this->eventInvoiceModel::where('event_id', $event->id)
            ->pluck('invoice_id')
            ->all();

        return $this->invoiceService->getInvoicesWithBalanceByIds($invoiceIds);
    }

    public function getInvoiceForEvent(Event $event, string $invoiceId): Invoice
    {
        $this->eventInvoiceModel::where('event_id', $event->id)
            ->where('invoice_id', $invoiceId)
            ->firstOrFail();

        return $this->invoiceService->getInvoiceForAccount($invoiceId);
    }
}
