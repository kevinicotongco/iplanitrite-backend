<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesInvoiceFixtures;
use Tests\TestCase;

class StaffInvoicePaymentManagementTest extends TestCase
{
    use CreatesInvoiceFixtures, RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInvoiceFixtures();
        $this->event = $this->createEvent();
    }

    private function paymentsUrl(Invoice $invoice, string $suffix = ''): string
    {
        return $this->invoicesUrl($this->event, '/' . $invoice->id . '/payments' . $suffix);
    }

    /**
     * @return array<string, mixed>
     */
    private function cashPayload(array $overrides = []): array
    {
        return array_merge([
            'description' => 'Deposit',
            'paymentMethod' => 'Cash',
            'amount' => '400.00',
        ], $overrides);
    }

    // GET /payments

    public function test_staff_can_list_invoice_payments_with_proof_document(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $document = $this->createDocument();
        $payment = $this->createPayment($invoice, [
            'description' => null,
            'payment_method' => 'BankTransfer',
            'bank_name' => 'BDO',
            'account_number' => '123',
            'amount' => '100.00',
            'proof_document_id' => $document->id,
        ]);
        $this->createPayment($this->createInvoice($this->event));

        $this->withToken($this->token)->getJson($this->paymentsUrl($invoice))
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $payment->id)
            ->assertJsonPath('0.status', 'ForReview')
            ->assertJsonPath('0.description', null)
            ->assertJsonPath('0.paymentMethod', 'BankTransfer')
            ->assertJsonPath('0.bankName', 'BDO')
            ->assertJsonPath('0.accountNumber', '123')
            ->assertJsonPath('0.amount', '100.00')
            ->assertJsonPath('0.proofDocument.id', $document->id)
            ->assertJsonStructure(['*' => ['id', 'status', 'description', 'paymentMethod', 'bankName', 'accountNumber', 'amount', 'proofDocument']]);
    }

    public function test_cash_payment_lists_null_bank_fields_and_proof(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $this->createPayment($invoice);

        $this->withToken($this->token)->getJson($this->paymentsUrl($invoice))
            ->assertStatus(200)
            ->assertJsonPath('0.bankName', null)
            ->assertJsonPath('0.accountNumber', null)
            ->assertJsonPath('0.proofDocument', null);
    }

    public function test_list_payments_returns_404_for_invoice_of_another_event(): void
    {
        $invoice = $this->createInvoice($this->createEvent());

        $this->withToken($this->token)->getJson($this->paymentsUrl($invoice))->assertStatus(404);
    }

    public function test_list_payments_requires_authentication(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->getJson($this->paymentsUrl($invoice))->assertStatus(401);
        $this->actingAs($this->client, 'client')->getJson($this->paymentsUrl($invoice))->assertStatus(401);
    }

    // POST /payments

    public function test_staff_can_create_cash_payment(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload())
            ->assertStatus(201)
            ->assertExactJson([]);

        $payment = InvoicePayment::firstOrFail();

        $this->assertSame($invoice->id, $payment->invoice_id);
        $this->assertSame(InvoicePaymentStatusEnum::ForReview, $payment->status);
        $this->assertSame('Deposit', $payment->description);
        $this->assertNull($payment->bank_name);
        $this->assertNull($payment->account_number);
        $this->assertSame('400.00', $payment->amount);
        $this->assertDatabaseHas('invoice_payment_logs', [
            'invoice_payment_id' => $payment->id,
            'audit_by' => $this->staff->id,
            'audit_type' => 'Staff',
            'action' => 'Create',
        ]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'Ready']);
    }

    public function test_staff_can_create_bank_transfer_payment_with_snapshot_and_proof(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $bankDetail = $this->createBankDetail();
        $document = $this->createDocument();

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), [
            'paymentMethod' => 'BankTransfer',
            'accountBankDetailId' => $bankDetail->id,
            'amount' => '1000.00',
            'proofDocumentId' => $document->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
            'payment_method' => 'BankTransfer',
            'bank_name' => $bankDetail->bank_name,
            'account_number' => $bankDetail->account_number,
            'description' => null,
            'proof_document_id' => $document->id,
            'status' => 'ForReview',
        ]);

        $bankDetail->update(['bank_name' => 'Changed Bank']);
        $this->assertDatabaseMissing('invoice_payments', ['bank_name' => 'Changed Bank']);
    }

    public function test_staff_can_create_payment_on_partially_paid_invoice(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $invoice->update(['status' => InvoiceStatusEnum::PartiallyPaid]);

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload())->assertStatus(201);
    }

    public function test_create_payment_validation_failures(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), [])
            ->assertStatus(422)->assertJsonValidationErrors(['paymentMethod', 'amount']);

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['paymentMethod' => 'Cheque']))
            ->assertStatus(422)->assertJsonValidationErrors(['paymentMethod']);

        foreach (['abc', '0', '-1', '1.999'] as $amount) {
            $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['amount' => $amount]))
                ->assertStatus(422)->assertJsonValidationErrors(['amount']);
        }

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['proofDocumentId' => 'nope']))
            ->assertStatus(422)->assertJsonValidationErrors(['proofDocumentId']);

        $this->assertDatabaseCount('invoice_payments', 0);
    }

    public function test_bank_transfer_requires_bank_detail_and_cash_prohibits_it(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $bankDetail = $this->createBankDetail();

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['paymentMethod' => 'BankTransfer']))
            ->assertStatus(422)->assertJsonValidationErrors(['accountBankDetailId']);

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['accountBankDetailId' => $bankDetail->id]))
            ->assertStatus(422)->assertJsonValidationErrors(['accountBankDetailId']);

        $this->assertDatabaseCount('invoice_payments', 0);
    }

    public function test_bank_detail_must_exist_and_belong_to_the_account(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $otherStaff = $this->createStaff($this->otherAccount, 'other@test.com');
        $otherBankDetail = $this->createBankDetail($this->otherAccount, $otherStaff);
        $deleted = $this->createBankDetail();
        $deleted->delete();

        foreach ([(string) Str::uuid(), $otherBankDetail->id, $deleted->id] as $bankDetailId) {
            $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload([
                'paymentMethod' => 'BankTransfer',
                'accountBankDetailId' => $bankDetailId,
            ]))->assertStatus(422)->assertJsonValidationErrors(['accountBankDetailId']);
        }

        $this->assertDatabaseCount('invoice_payments', 0);
    }

    public function test_proof_document_must_exist_not_be_deleted_and_belong_to_the_account(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $otherAccountDocument = $this->createDocument($this->otherAccount);
        $deletedDocument = $this->createDocument();
        $deletedDocument->delete();

        foreach ([(string) Str::uuid(), $deletedDocument->id, $otherAccountDocument->id] as $documentId) {
            $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['proofDocumentId' => $documentId]))
                ->assertStatus(422)->assertJsonValidationErrors(['proofDocumentId']);
        }

        $this->assertDatabaseCount('invoice_payments', 0);
    }

    public function test_payment_cannot_exceed_balance_due(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $this->createPayment($invoice, ['status' => InvoicePaymentStatusEnum::Approved, 'amount' => '600.00']);

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['amount' => '400.01']))
            ->assertStatus(422)->assertJsonValidationErrors(['amount']);

        $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload(['amount' => '400.00']))
            ->assertStatus(201);
    }

    public function test_payment_cannot_be_created_unless_invoice_is_ready_or_partially_paid(): void
    {
        foreach ([InvoiceStatusEnum::Pending, InvoiceStatusEnum::FullyPaid, InvoiceStatusEnum::Canceled] as $status) {
            $invoice = $this->createInvoice($this->event, ['status' => $status]);
            $this->createItem($invoice, ['amount' => '1000.00']);

            $this->withToken($this->token)->postJson($this->paymentsUrl($invoice), $this->cashPayload())
                ->assertStatus(422)->assertJsonValidationErrors(['status']);
        }

        $this->assertDatabaseCount('invoice_payments', 0);
    }

    public function test_create_payment_returns_404_for_unknown_invoice(): void
    {
        $this->withToken($this->token)
            ->postJson($this->invoicesUrl($this->event, '/' . Str::uuid() . '/payments'), $this->cashPayload())
            ->assertStatus(404);
    }

    public function test_create_payment_requires_authentication(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);

        $this->postJson($this->paymentsUrl($invoice), $this->cashPayload())->assertStatus(401);
        $this->actingAs($this->client, 'client')->postJson($this->paymentsUrl($invoice), $this->cashPayload())->assertStatus(401);
    }

    // PUT /payments/{paymentId}/status

    public function test_approving_partial_payment_makes_invoice_partially_paid(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $payment = $this->createPayment($invoice, ['amount' => '400.00']);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $payment->id . '/status'), ['status' => 'Approved'])
            ->assertStatus(200)
            ->assertExactJson([]);

        $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id, 'status' => 'Approved']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'PartiallyPaid']);
        $this->assertDatabaseHas('invoice_payment_logs', [
            'invoice_payment_id' => $payment->id,
            'audit_by' => $this->staff->id,
            'audit_type' => 'Staff',
            'action' => 'Update',
        ]);
    }

    public function test_approving_full_amount_makes_invoice_fully_paid(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $payment = $this->createPayment($invoice, ['amount' => '1000.00']);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $payment->id . '/status'), ['status' => 'Approved'])
            ->assertStatus(200);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'FullyPaid']);
    }

    public function test_approving_remaining_balance_moves_partially_paid_to_fully_paid(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $first = $this->createPayment($invoice, ['amount' => '400.00']);
        $second = $this->createPayment($invoice, ['amount' => '600.00']);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $first->id . '/status'), ['status' => 'Approved'])
            ->assertStatus(200);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'PartiallyPaid']);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $second->id . '/status'), ['status' => 'Approved'])
            ->assertStatus(200);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'FullyPaid']);
    }

    public function test_rejecting_payment_does_not_change_invoice_status(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $payment = $this->createPayment($invoice, ['amount' => '400.00']);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $payment->id . '/status'), ['status' => 'Rejected'])
            ->assertStatus(200);

        $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id, 'status' => 'Rejected']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'Ready']);
    }

    public function test_approval_that_would_exceed_invoice_total_is_rejected(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $first = $this->createPayment($invoice, ['amount' => '700.00']);
        $second = $this->createPayment($invoice, ['amount' => '700.00']);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $first->id . '/status'), ['status' => 'Approved'])
            ->assertStatus(200);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $second->id . '/status'), ['status' => 'Approved'])
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('invoice_payments', ['id' => $second->id, 'status' => 'ForReview']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'PartiallyPaid']);
    }

    public function test_only_for_review_payments_can_change_status(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');

        foreach ([InvoicePaymentStatusEnum::Approved, InvoicePaymentStatusEnum::Rejected] as $status) {
            $payment = $this->createPayment($invoice, ['status' => $status, 'amount' => '10.00']);

            foreach (['Approved', 'Rejected'] as $target) {
                $this->withToken($this->token)
                    ->putJson($this->paymentsUrl($invoice, '/' . $payment->id . '/status'), ['status' => $target])
                    ->assertStatus(422)->assertJsonValidationErrors(['status']);
            }

            $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id, 'status' => $status->value]);
        }
    }

    public function test_payment_status_validation_failures(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $payment = $this->createPayment($invoice);
        $url = $this->paymentsUrl($invoice, '/' . $payment->id . '/status');

        $this->withToken($this->token)->putJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->withToken($this->token)->putJson($url, ['status' => 'ForReview'])->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->withToken($this->token)->putJson($url, ['status' => 'Bogus'])->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id, 'status' => 'ForReview']);
    }

    public function test_payments_cannot_be_reviewed_on_pending_or_canceled_invoices(): void
    {
        foreach ([InvoiceStatusEnum::Pending, InvoiceStatusEnum::Canceled] as $status) {
            $invoice = $this->createInvoice($this->event, ['status' => $status]);
            $this->createItem($invoice, ['amount' => '1000.00']);
            $payment = $this->createPayment($invoice, ['amount' => '10.00']);

            $this->withToken($this->token)
                ->putJson($this->paymentsUrl($invoice, '/' . $payment->id . '/status'), ['status' => 'Approved'])
                ->assertStatus(422)->assertJsonValidationErrors(['status']);

            $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id, 'status' => 'ForReview']);
        }
    }

    public function test_payment_can_be_rejected_on_fully_paid_invoice(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $invoice->update(['status' => InvoiceStatusEnum::FullyPaid]);
        $payment = $this->createPayment($invoice, ['amount' => '10.00']);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $payment->id . '/status'), ['status' => 'Rejected'])
            ->assertStatus(200);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'FullyPaid']);
    }

    public function test_payment_status_returns_404_for_unknown_payment_or_payment_of_another_invoice(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $otherPayment = $this->createPayment($this->createReadyInvoiceWithItem($this->event));

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . Str::uuid() . '/status'), ['status' => 'Approved'])
            ->assertStatus(404);

        $this->withToken($this->token)
            ->putJson($this->paymentsUrl($invoice, '/' . $otherPayment->id . '/status'), ['status' => 'Approved'])
            ->assertStatus(404);

        $this->assertDatabaseHas('invoice_payments', ['id' => $otherPayment->id, 'status' => 'ForReview']);
    }

    public function test_payment_status_requires_authentication(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event);
        $payment = $this->createPayment($invoice);
        $url = $this->paymentsUrl($invoice, '/' . $payment->id . '/status');

        $this->putJson($url, ['status' => 'Approved'])->assertStatus(401);
        $this->actingAs($this->client, 'client')->putJson($url, ['status' => 'Approved'])->assertStatus(401);
    }
}
