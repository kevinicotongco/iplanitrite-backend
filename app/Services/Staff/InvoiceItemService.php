<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Dto\Request\CreateInvoiceItemRequestDto;
use App\Dto\Request\EditInvoiceItemRequestDto;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Collection;

readonly class InvoiceItemService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private InvoiceItem $invoiceItemModel,
    ) {}

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getItemsForInvoice(Invoice $invoice): Collection
    {
        return $this->invoiceItemModel::where('invoice_id', $invoice->id)
            ->orderBy('created_at')
            ->get();
    }

    public function createItem(Invoice $invoice, CreateInvoiceItemRequestDto $dto): InvoiceItem
    {
        return $this->invoiceItemModel::create([
            'invoice_id' => $invoice->id,
            'description' => $dto->description,
            'amount' => $dto->amount,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    public function updateItem(Invoice $invoice, string $invoiceItemId, EditInvoiceItemRequestDto $dto): InvoiceItem
    {
        $item = $this->invoiceItemModel::where('id', $invoiceItemId)
            ->where('invoice_id', $invoice->id)
            ->firstOrFail();

        $item->update([
            'description' => $dto->description,
            'amount' => $dto->amount,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $item;
    }

    /**
     * @param array<int, string> $invoiceIds
     * @return array<string, string> invoice id => total amount
     */
    public function getTotalsByInvoiceIds(array $invoiceIds): array
    {
        return $this->invoiceItemModel::whereIn('invoice_id', $invoiceIds)
            ->selectRaw('invoice_id, SUM(amount)::text AS total')
            ->groupBy('invoice_id')
            ->pluck('total', 'invoice_id')
            ->all();
    }
}
