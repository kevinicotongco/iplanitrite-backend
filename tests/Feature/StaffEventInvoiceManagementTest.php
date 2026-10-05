<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Models\Event;
use App\Models\EventInvoice;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesInvoiceFixtures;
use Tests\TestCase;

class StaffEventInvoiceManagementTest extends TestCase
{
    use CreatesInvoiceFixtures, RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInvoiceFixtures();
        $this->event = $this->createEvent();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedRoutes(): array
    {
        return [
            'list' => ['GET', ''],
            'create' => ['POST', ''],
            'show' => ['GET', '/{invoice}'],
            'due date' => ['PUT', '/{invoice}/due-date'],
            'ready' => ['PUT', '/{invoice}/ready'],
            'pending' => ['PUT', '/{invoice}/pending'],
            'cancel' => ['PUT', '/{invoice}/cancel'],
            'list payments' => ['GET', '/{invoice}/payments'],
            'create payment' => ['POST', '/{invoice}/payments'],
            'payment status' => ['PUT', '/{invoice}/payments/{invoice}/status'],
            'list items' => ['GET', '/{invoice}/items'],
            'create item' => ['POST', '/{invoice}/items'],
            'edit item' => ['PUT', '/{invoice}/items/{invoice}'],
        ];
    }

    private function resolveUrl(string $suffix): string
    {
        return $this->invoicesUrl($this->event, str_replace('{invoice}', (string) Str::uuid(), $suffix));
    }

    #[DataProvider('protectedRoutes')]
    public function test_invoice_routes_reject_unauthenticated_requests(string $method, string $suffix): void
    {
        $this->json($method, $this->resolveUrl($suffix))->assertStatus(401);
    }

    #[DataProvider('protectedRoutes')]
    public function test_invoice_routes_reject_client_and_admin_users(string $method, string $suffix): void
    {
        $this->actingAs($this->client, 'client')->json($method, $this->resolveUrl($suffix))->assertStatus(401);
        $this->actingAs($this->admin, 'admin')->json($method, $this->resolveUrl($suffix))->assertStatus(401);
    }

    // GET /invoices

    public function test_staff_can_list_event_invoices_with_balance_due(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '1000.00');
        $this->createItem($invoice, ['amount' => '500.50']);
        $this->createPayment($invoice, ['status' => InvoicePaymentStatusEnum::Approved, 'amount' => '300.25']);
        $this->createPayment($invoice, ['status' => InvoicePaymentStatusEnum::ForReview, 'amount' => '100.00']);
        $this->createPayment($invoice, ['status' => InvoicePaymentStatusEnum::Rejected, 'amount' => '50.00']);
        $this->createInvoice($this->createEvent());

        $this->withToken($this->token)->getJson($this->invoicesUrl($this->event))
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $invoice->id)
            ->assertJsonPath('0.invoiceNumber', $invoice->invoice_number)
            ->assertJsonPath('0.status', 'Ready')
            ->assertJsonPath('0.cancellationNotes', null)
            ->assertJsonPath('0.balanceDue', '1200.25')
            ->assertJsonStructure(['*' => ['id', 'invoiceNumber', 'status', 'cancellationNotes', 'dueDate', 'balanceDue']]);
    }

    public function test_list_invoices_returns_empty_array_when_event_has_none(): void
    {
        $this->withToken($this->token)->getJson($this->invoicesUrl($this->event))
            ->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_list_invoices_returns_404_for_other_account_event(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);

        $this->withToken($this->token)->getJson($this->invoicesUrl($otherEvent))->assertStatus(404);
    }

    public function test_list_invoices_returns_404_for_unknown_event(): void
    {
        $this->withToken($this->token)->getJson('/api/staff/events/' . Str::uuid() . '/invoices')->assertStatus(404);
    }

    // POST /invoices

    public function test_staff_can_create_invoice(): void
    {
        $this->withToken($this->token)->postJson($this->invoicesUrl($this->event))
            ->assertStatus(201)
            ->assertExactJson([]);

        $invoice = Invoice::firstOrFail();

        $this->assertSame('INV-00001', $invoice->invoice_number);
        $this->assertSame(InvoiceStatusEnum::Pending, $invoice->status);
        $this->assertSame($this->account->id, $invoice->account_id);
        $this->assertSame(now()->addWeeks(2)->toDateString(), $invoice->due_date->toDateString());
        $this->assertSame($this->staff->id, $invoice->created_by);
        $this->assertSame($this->staff->id, $invoice->updated_by);
        $this->assertDatabaseHas('event_invoices', ['event_id' => $this->event->id, 'invoice_id' => $invoice->id]);
    }

    public function test_invoice_numbers_increment_per_account(): void
    {
        $otherStaff = $this->createStaff($this->otherAccount, 'other@test.com');
        $otherEvent = $this->createEvent(account: $this->otherAccount);
        $otherToken = $otherStaff->createToken('t')->plainTextToken;

        $this->withToken($this->token)->postJson($this->invoicesUrl($this->event))->assertStatus(201);
        $this->withToken($this->token)->postJson($this->invoicesUrl($this->event))->assertStatus(201);
        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)->postJson($this->invoicesUrl($otherEvent))->assertStatus(201);

        $this->assertSame(
            ['INV-00001', 'INV-00002'],
            Invoice::where('account_id', $this->account->id)->orderBy('invoice_number')->pluck('invoice_number')->all()
        );
        $this->assertSame(['INV-00001'], Invoice::where('account_id', $this->otherAccount->id)->pluck('invoice_number')->all());
    }

    public function test_invoice_number_never_reuses_deleted_numbers_and_grows_past_five_digits(): void
    {
        $this->createInvoice($this->event, ['invoice_number' => 'INV-99999'])->delete();

        $this->withToken($this->token)->postJson($this->invoicesUrl($this->event))->assertStatus(201);

        $this->assertDatabaseHas('invoices', ['account_id' => $this->account->id, 'invoice_number' => 'INV-100000']);
    }

    public function test_create_invoice_returns_404_for_other_account_event(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);

        $this->withToken($this->token)->postJson($this->invoicesUrl($otherEvent))->assertStatus(404);
        $this->assertDatabaseCount('invoices', 0);
    }

    // GET /invoices/{invoiceId}

    public function test_staff_can_show_invoice_with_bank_details(): void
    {
        $invoice = $this->createReadyInvoiceWithItem($this->event, '250.00');
        $bankDetail = $this->createBankDetail();
        $otherStaff = $this->createStaff($this->otherAccount, 'other@test.com');
        $this->createBankDetail($this->otherAccount, $otherStaff);

        $this->withToken($this->token)->getJson($this->invoicesUrl($this->event, '/' . $invoice->id))
            ->assertStatus(200)
            ->assertJsonPath('invoice.id', $invoice->id)
            ->assertJsonPath('invoice.balanceDue', '250.00')
            ->assertJsonCount(1, 'bankDetails')
            ->assertJsonPath('bankDetails.0.id', $bankDetail->id)
            ->assertJsonMissingPath('invoiceItems')
            ->assertJsonStructure([
                'invoice' => ['id', 'invoiceNumber', 'status', 'cancellationNotes', 'dueDate', 'balanceDue'],
                'bankDetails' => ['*' => ['id', 'bankName', 'accountNumber', 'qrCode']],
            ]);
    }

    public function test_show_invoice_returns_404_when_invoice_belongs_to_another_event(): void
    {
        $invoice = $this->createInvoice($this->createEvent());

        $this->withToken($this->token)->getJson($this->invoicesUrl($this->event, '/' . $invoice->id))->assertStatus(404);
    }

    public function test_show_invoice_returns_404_for_unknown_invoice(): void
    {
        $this->withToken($this->token)->getJson($this->invoicesUrl($this->event, '/' . Str::uuid()))->assertStatus(404);
    }

    // PUT /invoices/{invoiceId}/due-date

    public function test_staff_can_update_due_date_of_pending_invoice(): void
    {
        $invoice = $this->createInvoice($this->event);
        $dueDate = now()->addDays(30)->toDateString();

        $this->withToken($this->token)
            ->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/due-date'), ['dueDate' => $dueDate])
            ->assertStatus(200)
            ->assertExactJson([]);

        $this->assertSame($dueDate, $invoice->fresh()->due_date->toDateString());
    }

    public function test_due_date_can_be_today(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->withToken($this->token)
            ->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/due-date'), ['dueDate' => now()->toDateString()])
            ->assertStatus(200);
    }

    public function test_due_date_validation_failures(): void
    {
        $invoice = $this->createInvoice($this->event);
        $url = $this->invoicesUrl($this->event, '/' . $invoice->id . '/due-date');
        $original = $invoice->due_date->toDateString();

        $this->withToken($this->token)->putJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['dueDate']);
        $this->withToken($this->token)->putJson($url, ['dueDate' => 'not-a-date'])->assertStatus(422)->assertJsonValidationErrors(['dueDate']);
        $this->withToken($this->token)->putJson($url, ['dueDate' => now()->subDay()->toDateString()])->assertStatus(422)->assertJsonValidationErrors(['dueDate']);

        $this->assertSame($original, $invoice->fresh()->due_date->toDateString());
    }

    public function test_due_date_cannot_change_unless_pending(): void
    {
        $invoice = $this->createInvoice($this->event, ['status' => InvoiceStatusEnum::Ready]);

        $this->withToken($this->token)
            ->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/due-date'), ['dueDate' => now()->addDays(30)->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_due_date_returns_404_for_unknown_invoice(): void
    {
        $this->withToken($this->token)
            ->putJson($this->invoicesUrl($this->event, '/' . Str::uuid() . '/due-date'), ['dueDate' => now()->addDay()->toDateString()])
            ->assertStatus(404);
    }

    // PUT /invoices/{invoiceId}/ready

    public function test_staff_can_mark_pending_invoice_ready(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/ready'))
            ->assertStatus(200)
            ->assertExactJson([]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'Ready']);
    }

    public function test_only_pending_invoice_can_be_marked_ready(): void
    {
        foreach ([InvoiceStatusEnum::Ready, InvoiceStatusEnum::PartiallyPaid, InvoiceStatusEnum::FullyPaid, InvoiceStatusEnum::Canceled] as $status) {
            $invoice = $this->createInvoice($this->event, ['status' => $status]);

            $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/ready'))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['status']);

            $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => $status->value]);
        }
    }

    public function test_ready_returns_404_for_unknown_invoice(): void
    {
        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . Str::uuid() . '/ready'))->assertStatus(404);
    }

    // PUT /invoices/{invoiceId}/pending

    public function test_staff_can_move_ready_invoice_back_to_pending(): void
    {
        $invoice = $this->createInvoice($this->event, ['status' => InvoiceStatusEnum::Ready]);

        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/pending'))
            ->assertStatus(200)
            ->assertExactJson([]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'Pending']);
    }

    public function test_only_ready_invoice_can_be_moved_to_pending(): void
    {
        foreach ([InvoiceStatusEnum::Pending, InvoiceStatusEnum::PartiallyPaid, InvoiceStatusEnum::FullyPaid, InvoiceStatusEnum::Canceled] as $status) {
            $invoice = $this->createInvoice($this->event, ['status' => $status]);

            $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/pending'))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['status']);

            $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => $status->value]);
        }
    }

    public function test_pending_returns_404_for_invoice_of_another_event(): void
    {
        $invoice = $this->createInvoice($this->createEvent(), ['status' => InvoiceStatusEnum::Ready]);

        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/pending'))->assertStatus(404);
    }

    // PUT /invoices/{invoiceId}/cancel

    public function test_staff_can_cancel_invoice_from_any_status_with_notes(): void
    {
        foreach ([InvoiceStatusEnum::Pending, InvoiceStatusEnum::Ready, InvoiceStatusEnum::PartiallyPaid, InvoiceStatusEnum::FullyPaid] as $status) {
            $invoice = $this->createInvoice($this->event, ['status' => $status]);

            $this->withToken($this->token)
                ->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/cancel'), ['cancellationNotes' => 'Client request'])
                ->assertStatus(200)
                ->assertExactJson([]);

            $this->assertDatabaseHas('invoices', [
                'id' => $invoice->id,
                'status' => 'Canceled',
                'cancellation_notes' => 'Client request',
            ]);
        }
    }

    public function test_cancel_notes_are_optional(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/cancel'))->assertStatus(200);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'Canceled', 'cancellation_notes' => null]);
    }

    public function test_cancel_validates_notes_length(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->withToken($this->token)
            ->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/cancel'), ['cancellationNotes' => str_repeat('a', 2001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cancellationNotes']);
    }

    public function test_cannot_cancel_an_already_canceled_invoice(): void
    {
        $invoice = $this->createInvoice($this->event, ['status' => InvoiceStatusEnum::Canceled]);

        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/cancel'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_cancel_returns_404_for_unknown_invoice(): void
    {
        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . Str::uuid() . '/cancel'))->assertStatus(404);
    }

    public function test_invoice_of_other_account_cannot_be_reached(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);
        $otherStaff = $this->createStaff($this->otherAccount, 'other@test.com');
        $invoice = $this->createInvoice($otherEvent, [], $otherStaff);

        $this->withToken($this->token)->putJson($this->invoicesUrl($this->event, '/' . $invoice->id . '/ready'))->assertStatus(404);
        $this->assertSame(1, EventInvoice::where('invoice_id', $invoice->id)->count());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'Pending']);
    }
}
