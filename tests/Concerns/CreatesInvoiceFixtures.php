<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\AccountRolePermissionEnum;
use App\Enums\InvoicePaymentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Models\Account;
use App\Models\AccountBankDetail;
use App\Models\AccountRolePermission;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventInvoice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Staff;
use Illuminate\Support\Str;

trait CreatesInvoiceFixtures
{
    use CreatesEventPricingFixtures;

    protected function setUpInvoiceFixtures(): void
    {
        $this->setUpEventPricingFixtures();
    }

    /**
     * @param array<int, AccountRolePermissionEnum> $permissions
     */
    protected function grantPermissions(Staff $staff, array $permissions): void
    {
        foreach ($permissions as $permission) {
            AccountRolePermission::create([
                'id' => Str::uuid()->toString(),
                'account_role_id' => $staff->account_role_id,
                'permission' => $permission,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createInvoice(Event $event, array $attributes = [], ?Staff $staff = null): Invoice
    {
        $staff ??= $this->staff;

        $invoice = Invoice::factory()->create(array_merge([
            'account_id' => $event->account_id,
            'created_by' => $staff->id,
            'updated_by' => $staff->id,
        ], $attributes));

        EventInvoice::create([
            'event_id' => $event->id,
            'invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }

    protected function createReadyInvoiceWithItem(Event $event, string $amount = '1000.00'): Invoice
    {
        $invoice = $this->createInvoice($event, ['status' => InvoiceStatusEnum::Ready]);
        $this->createItem($invoice, ['amount' => $amount]);

        return $invoice;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createItem(Invoice $invoice, array $attributes = []): InvoiceItem
    {
        return InvoiceItem::factory()->create(array_merge([
            'invoice_id' => $invoice->id,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createPayment(Invoice $invoice, array $attributes = []): InvoicePayment
    {
        return InvoicePayment::factory()->create(array_merge([
            'invoice_id' => $invoice->id,
            'status' => InvoicePaymentStatusEnum::ForReview,
        ], $attributes));
    }

    protected function createBankDetail(?Account $account = null, ?Staff $staff = null): AccountBankDetail
    {
        $staff ??= $this->staff;

        return AccountBankDetail::factory()->create([
            'account_id' => ($account ?? $this->account)->id,
            'created_by' => $staff->id,
            'updated_by' => $staff->id,
        ]);
    }

    protected function createDocument(?Account $account = null): Document
    {
        return Document::create([
            'id' => Str::uuid()->toString(),
            'account_id' => ($account ?? $this->account)->id,
            'name' => 'proof.png',
            'display_name' => 'proof.png',
            'url' => 'http://localhost/storage/documents/proof.png',
            'size' => 1024,
            'mime_type' => 'image/png',
        ]);
    }

    protected function invoicesUrl(Event $event, string $suffix = ''): string
    {
        return '/api/staff/events/' . $event->id . '/invoices' . $suffix;
    }
}
