<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Dto\Request\CreatePaymentRequestDto;
use App\Enums\AuditActionEnum;
use App\Enums\InvoicePaymentStatusEnum;
use App\Models\AccountBankDetail;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

readonly class InvoicePaymentService
{
    public function __construct(
        private InvoicePayment $invoicePaymentModel,
        private AuditLogService $auditLogService,
    ) {}

    /**
     * @return Collection<int, InvoicePayment>
     */
    public function getPaymentsForInvoice(Invoice $invoice): Collection
    {
        return $this->invoicePaymentModel::where('invoice_id', $invoice->id)
            ->with('proofDocument')
            ->orderBy('created_at')
            ->get();
    }

    public function getPaymentForInvoice(Invoice $invoice, string $paymentId): InvoicePayment
    {
        return $this->invoicePaymentModel::where('id', $paymentId)
            ->where('invoice_id', $invoice->id)
            ->firstOrFail();
    }

    public function createPayment(Invoice $invoice, CreatePaymentRequestDto $dto, ?AccountBankDetail $bankDetail): InvoicePayment
    {
        $payment = $this->invoicePaymentModel::create([
            'invoice_id' => $invoice->id,
            'status' => InvoicePaymentStatusEnum::ForReview,
            'description' => $dto->description,
            'payment_method' => $dto->paymentMethod,
            'bank_name' => $bankDetail?->bank_name,
            'account_number' => $bankDetail?->account_number,
            'amount' => $dto->amount,
            'proof_document_id' => $dto->proofDocumentId,
        ]);

        $this->auditLogService->logInvoicePaymentAction($payment, AuditActionEnum::Create);

        return $payment;
    }

    /**
     * @throws ValidationException
     */
    public function updateStatus(InvoicePayment $payment, InvoicePaymentStatusEnum $status): InvoicePayment
    {
        if ($payment->status !== InvoicePaymentStatusEnum::ForReview) {
            throw ValidationException::withMessages([
                'status' => 'Only payments that are for review can be approved or rejected.',
            ]);
        }

        $payment->update(['status' => $status]);

        $this->auditLogService->logInvoicePaymentAction($payment, AuditActionEnum::Update);

        return $payment;
    }

    /**
     * @param array<int, string> $invoiceIds
     * @return array<string, string> invoice id => approved total amount
     */
    public function getApprovedTotalsByInvoiceIds(array $invoiceIds): array
    {
        return $this->invoicePaymentModel::whereIn('invoice_id', $invoiceIds)
            ->where('status', InvoicePaymentStatusEnum::Approved)
            ->selectRaw('invoice_id, SUM(amount)::text AS total')
            ->groupBy('invoice_id')
            ->pluck('total', 'invoice_id')
            ->all();
    }
}
