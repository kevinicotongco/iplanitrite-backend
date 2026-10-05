<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Data\InvoiceShowData;
use App\Data\InvoiceWithBalanceData;
use App\Dto\Request\CancelInvoiceRequestDto;
use App\Dto\Request\CreateInvoiceItemRequestDto;
use App\Dto\Request\CreatePaymentRequestDto;
use App\Dto\Request\EditInvoiceItemRequestDto;
use App\Dto\Request\UpdateInvoiceDueDateRequestDto;
use App\Dto\Request\UpdateInvoicePaymentStatusRequestDto;
use App\Enums\InvoicePaymentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

readonly class InvoiceService
{
    private const NUMBER_PREFIX = 'INV-';
    private const NUMBER_PAD_LENGTH = 5;
    private const DUE_DATE_WEEKS = 2;
    private const MONEY_SCALE = 2;
    private const ZERO_AMOUNT = '0';

    /**
     * @var array<int, InvoiceStatusEnum>
     */
    private const PAYMENT_CREATABLE_STATUSES = [
        InvoiceStatusEnum::Ready,
        InvoiceStatusEnum::PartiallyPaid,
    ];

    /**
     * @var array<int, InvoiceStatusEnum>
     */
    private const PAYMENT_REVIEWABLE_STATUSES = [
        InvoiceStatusEnum::Ready,
        InvoiceStatusEnum::PartiallyPaid,
        InvoiceStatusEnum::FullyPaid,
    ];

    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private Invoice $invoiceModel,
        private InvoiceItemService $invoiceItemService,
        private InvoicePaymentService $invoicePaymentService,
        private AccountBankDetailService $accountBankDetailService,
        private DocumentService $documentService,
    ) {}

    public function getInvoiceForAccount(string $invoiceId): Invoice
    {
        return $this->invoiceModel::where('id', $invoiceId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();
    }

    public function createInvoice(): Invoice
    {
        $accountId = $this->authenticatedUser->accountId;

        DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [$accountId]);

        $lastNumber = $this->invoiceModel::withTrashed()
            ->where('account_id', $accountId)
            ->selectRaw(sprintf('MAX(CAST(SUBSTRING(invoice_number FROM %d) AS INTEGER)) AS last_number', strlen(self::NUMBER_PREFIX) + 1))
            ->value('last_number');

        $invoiceNumber = self::NUMBER_PREFIX . str_pad(
            (string) (((int) $lastNumber) + 1),
            self::NUMBER_PAD_LENGTH,
            '0',
            STR_PAD_LEFT
        );

        return $this->invoiceModel::create([
            'account_id' => $accountId,
            'invoice_number' => $invoiceNumber,
            'status' => InvoiceStatusEnum::Pending,
            'due_date' => now()->addWeeks(self::DUE_DATE_WEEKS)->toDateString(),
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    /**
     * @param array<int, string> $invoiceIds
     * @return Collection<int, InvoiceWithBalanceData>
     */
    public function getInvoicesWithBalanceByIds(array $invoiceIds): Collection
    {
        $invoices = $this->invoiceModel::whereIn('id', $invoiceIds)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->orderBy('created_at')
            ->get();

        $itemTotals = $this->invoiceItemService->getTotalsByInvoiceIds($invoiceIds);
        $approvedTotals = $this->invoicePaymentService->getApprovedTotalsByInvoiceIds($invoiceIds);

        return $invoices->map(fn(Invoice $invoice): InvoiceWithBalanceData => new InvoiceWithBalanceData(
            invoice: $invoice,
            balanceDue: bcsub(
                $itemTotals[$invoice->id] ?? self::ZERO_AMOUNT,
                $approvedTotals[$invoice->id] ?? self::ZERO_AMOUNT,
                self::MONEY_SCALE
            ),
        ))->values();
    }

    public function getInvoiceWithBalance(Invoice $invoice): InvoiceWithBalanceData
    {
        return new InvoiceWithBalanceData(
            invoice: $invoice,
            balanceDue: $this->calculateBalanceDue($invoice),
        );
    }

    public function getInvoiceShow(Invoice $invoice): InvoiceShowData
    {
        return new InvoiceShowData(
            invoice: $this->getInvoiceWithBalance($invoice),
            bankDetails: $this->accountBankDetailService->getBankDetails(),
        );
    }

    /**
     * @throws ValidationException
     */
    public function updateDueDate(Invoice $invoice, UpdateInvoiceDueDateRequestDto $dto): Invoice
    {
        $this->assertPending($invoice, 'The due date can only be changed while the invoice is pending.');

        $invoice->update([
            'due_date' => $dto->dueDate,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $invoice;
    }

    /**
     * @throws ValidationException
     */
    public function markReady(Invoice $invoice): Invoice
    {
        $this->assertStatus($invoice, InvoiceStatusEnum::Pending, 'Only a pending invoice can be marked as ready.');

        return $this->changeStatus($invoice, InvoiceStatusEnum::Ready);
    }

    /**
     * @throws ValidationException
     */
    public function markPending(Invoice $invoice): Invoice
    {
        $this->assertStatus($invoice, InvoiceStatusEnum::Ready, 'Only a ready invoice can be moved back to pending.');

        return $this->changeStatus($invoice, InvoiceStatusEnum::Pending);
    }

    /**
     * @throws ValidationException
     */
    public function cancel(Invoice $invoice, CancelInvoiceRequestDto $dto): Invoice
    {
        if ($invoice->status === InvoiceStatusEnum::Canceled) {
            throw ValidationException::withMessages([
                'status' => 'The invoice is already canceled.',
            ]);
        }

        $invoice->update([
            'status' => InvoiceStatusEnum::Canceled,
            'cancellation_notes' => $dto->cancellationNotes,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $invoice;
    }

    /**
     * @throws ValidationException
     */
    public function addItem(Invoice $invoice, CreateInvoiceItemRequestDto $dto): InvoiceItem
    {
        $this->assertPending($invoice, 'Items can only be added while the invoice is pending.');

        return $this->invoiceItemService->createItem($invoice, $dto);
    }

    /**
     * @throws ValidationException
     */
    public function updateItem(Invoice $invoice, string $invoiceItemId, EditInvoiceItemRequestDto $dto): InvoiceItem
    {
        $this->assertPending($invoice, 'Items can only be edited while the invoice is pending.');

        return $this->invoiceItemService->updateItem($invoice, $invoiceItemId, $dto);
    }

    /**
     * @throws ValidationException
     */
    public function addPayment(Invoice $invoice, CreatePaymentRequestDto $dto): InvoicePayment
    {
        if (!in_array($invoice->status, self::PAYMENT_CREATABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Payments can only be added to a ready or partially paid invoice.',
            ]);
        }

        if (bccomp($dto->amount, $this->calculateBalanceDue($invoice), self::MONEY_SCALE) > 0) {
            throw ValidationException::withMessages([
                'amount' => 'The amount cannot exceed the invoice balance due.',
            ]);
        }

        $bankDetail = null;

        if ($dto->paymentMethod === PaymentMethodEnum::BankTransfer) {
            $bankDetail = $this->accountBankDetailService->findBankDetailForAccount((string) $dto->accountBankDetailId);

            if ($bankDetail === null) {
                throw ValidationException::withMessages([
                    'accountBankDetailId' => 'The selected bank detail is invalid.',
                ]);
            }
        }

        if ($dto->proofDocumentId !== null) {
            $this->documentService->getDocumentForAccount($dto->proofDocumentId, 'proofDocumentId');
        }

        return $this->invoicePaymentService->createPayment($invoice, $dto, $bankDetail);
    }

    /**
     * @throws ValidationException
     */
    public function reviewPayment(Invoice $invoice, string $paymentId, UpdateInvoicePaymentStatusRequestDto $dto): InvoicePayment
    {
        if (!in_array($invoice->status, self::PAYMENT_REVIEWABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Payments can only be reviewed on a ready, partially paid or fully paid invoice.',
            ]);
        }

        $payment = $this->invoicePaymentService->getPaymentForInvoice($invoice, $paymentId);

        if ($dto->status === InvoicePaymentStatusEnum::Approved && $payment->status === InvoicePaymentStatusEnum::ForReview) {
            $approvedTotal = $this->getApprovedTotal($invoice);

            if (bccomp(bcadd($approvedTotal, $payment->amount, self::MONEY_SCALE), $this->getItemsTotal($invoice), self::MONEY_SCALE) > 0) {
                throw ValidationException::withMessages([
                    'status' => 'Approving this payment would exceed the invoice total.',
                ]);
            }
        }

        $payment = $this->invoicePaymentService->updateStatus($payment, $dto->status);

        if ($dto->status === InvoicePaymentStatusEnum::Approved) {
            $this->recalculateStatus($invoice);
        }

        return $payment;
    }

    private function recalculateStatus(Invoice $invoice): void
    {
        $approvedTotal = $this->getApprovedTotal($invoice);

        if (bccomp($approvedTotal, $this->getItemsTotal($invoice), self::MONEY_SCALE) === 0) {
            $this->changeStatus($invoice, InvoiceStatusEnum::FullyPaid);

            return;
        }

        if (bccomp($approvedTotal, self::ZERO_AMOUNT, self::MONEY_SCALE) > 0) {
            $this->changeStatus($invoice, InvoiceStatusEnum::PartiallyPaid);
        }
    }

    private function calculateBalanceDue(Invoice $invoice): string
    {
        return bcsub($this->getItemsTotal($invoice), $this->getApprovedTotal($invoice), self::MONEY_SCALE);
    }

    private function getItemsTotal(Invoice $invoice): string
    {
        return $this->invoiceItemService->getTotalsByInvoiceIds([$invoice->id])[$invoice->id] ?? self::ZERO_AMOUNT;
    }

    private function getApprovedTotal(Invoice $invoice): string
    {
        return $this->invoicePaymentService->getApprovedTotalsByInvoiceIds([$invoice->id])[$invoice->id] ?? self::ZERO_AMOUNT;
    }

    private function changeStatus(Invoice $invoice, InvoiceStatusEnum $status): Invoice
    {
        $invoice->update([
            'status' => $status,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $invoice;
    }

    /**
     * @throws ValidationException
     */
    private function assertPending(Invoice $invoice, string $message): void
    {
        $this->assertStatus($invoice, InvoiceStatusEnum::Pending, $message);
    }

    /**
     * @throws ValidationException
     */
    private function assertStatus(Invoice $invoice, InvoiceStatusEnum $expected, string $message): void
    {
        if ($invoice->status !== $expected) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
