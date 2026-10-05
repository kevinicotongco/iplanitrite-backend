<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceStatusEnum;
use App\Models\Event;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesInvoiceFixtures;
use Tests\TestCase;

class StaffInvoiceItemManagementTest extends TestCase
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
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge(['description' => 'Venue rental', 'amount' => '1500.50'], $overrides);
    }

    private function itemsUrl(Invoice $invoice, string $suffix = ''): string
    {
        return $this->invoicesUrl($this->event, '/' . $invoice->id . '/items' . $suffix);
    }

    // GET /items

    public function test_staff_can_list_invoice_items(): void
    {
        $invoice = $this->createInvoice($this->event);
        $first = $this->createItem($invoice, ['description' => 'Catering', 'amount' => '200.00']);
        $this->createItem($invoice, ['description' => 'Flowers', 'amount' => '75.5']);
        $this->createItem($this->createInvoice($this->event));

        $this->withToken($this->token)->getJson($this->itemsUrl($invoice))
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('0.description', 'Catering')
            ->assertJsonPath('0.amount', '200.00')
            ->assertJsonPath('1.amount', '75.50')
            ->assertJsonStructure(['*' => ['id', 'description', 'amount']]);
    }

    public function test_list_items_returns_404_for_invoice_of_another_event(): void
    {
        $invoice = $this->createInvoice($this->createEvent());

        $this->withToken($this->token)->getJson($this->itemsUrl($invoice))->assertStatus(404);
    }

    public function test_list_items_requires_authentication(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->getJson($this->itemsUrl($invoice))->assertStatus(401);
        $this->actingAs($this->client, 'client')->getJson($this->itemsUrl($invoice))->assertStatus(401);
    }

    // POST /items

    public function test_staff_can_add_item_to_pending_invoice(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->withToken($this->token)->postJson($this->itemsUrl($invoice), $this->validPayload())
            ->assertStatus(201)
            ->assertExactJson([]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Venue rental',
            'amount' => '1500.50',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_add_item_validation_failures(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->withToken($this->token)->postJson($this->itemsUrl($invoice), [])
            ->assertStatus(422)->assertJsonValidationErrors(['description', 'amount']);

        foreach (['abc', '0', '-5', '10.999', '100000000'] as $amount) {
            $this->withToken($this->token)->postJson($this->itemsUrl($invoice), $this->validPayload(['amount' => $amount]))
                ->assertStatus(422)->assertJsonValidationErrors(['amount']);
        }

        $this->assertDatabaseCount('invoice_items', 0);
    }

    public function test_cannot_add_item_unless_invoice_is_pending(): void
    {
        $invoice = $this->createInvoice($this->event, ['status' => InvoiceStatusEnum::Ready]);

        $this->withToken($this->token)->postJson($this->itemsUrl($invoice), $this->validPayload())
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->assertDatabaseCount('invoice_items', 0);
    }

    public function test_add_item_returns_404_for_unknown_invoice(): void
    {
        $this->withToken($this->token)
            ->postJson($this->invoicesUrl($this->event, '/' . Str::uuid() . '/items'), $this->validPayload())
            ->assertStatus(404);
    }

    public function test_add_item_requires_authentication(): void
    {
        $invoice = $this->createInvoice($this->event);

        $this->postJson($this->itemsUrl($invoice), $this->validPayload())->assertStatus(401);
        $this->actingAs($this->client, 'client')->postJson($this->itemsUrl($invoice), $this->validPayload())->assertStatus(401);
    }

    // PUT /items/{invoiceItemId}

    public function test_staff_can_edit_item_of_pending_invoice(): void
    {
        $invoice = $this->createInvoice($this->event);
        $item = $this->createItem($invoice, ['description' => 'Old', 'amount' => '10.00']);
        $editor = $this->createStaff($this->account, 'editor@test.com');
        $editorToken = $editor->createToken('t')->plainTextToken;

        $this->withToken($editorToken)->putJson($this->itemsUrl($invoice, '/' . $item->id), $this->validPayload())
            ->assertStatus(200)
            ->assertExactJson([]);

        $this->assertDatabaseHas('invoice_items', [
            'id' => $item->id,
            'description' => 'Venue rental',
            'amount' => '1500.50',
            'created_by' => $this->staff->id,
            'updated_by' => $editor->id,
        ]);
    }

    public function test_edit_item_validation_failures(): void
    {
        $invoice = $this->createInvoice($this->event);
        $item = $this->createItem($invoice, ['description' => 'Old', 'amount' => '10.00']);

        $this->withToken($this->token)->putJson($this->itemsUrl($invoice, '/' . $item->id), ['amount' => '0'])
            ->assertStatus(422)->assertJsonValidationErrors(['description', 'amount']);

        $this->assertDatabaseHas('invoice_items', ['id' => $item->id, 'description' => 'Old', 'amount' => '10.00']);
    }

    public function test_cannot_edit_item_unless_invoice_is_pending(): void
    {
        $invoice = $this->createInvoice($this->event, ['status' => InvoiceStatusEnum::PartiallyPaid]);
        $item = $this->createItem($invoice, ['description' => 'Old']);

        $this->withToken($this->token)->putJson($this->itemsUrl($invoice, '/' . $item->id), $this->validPayload())
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('invoice_items', ['id' => $item->id, 'description' => 'Old']);
    }

    public function test_edit_item_returns_404_for_unknown_item_or_item_of_another_invoice(): void
    {
        $invoice = $this->createInvoice($this->event);
        $otherItem = $this->createItem($this->createInvoice($this->event), ['description' => 'Other']);

        $this->withToken($this->token)->putJson($this->itemsUrl($invoice, '/' . Str::uuid()), $this->validPayload())->assertStatus(404);
        $this->withToken($this->token)->putJson($this->itemsUrl($invoice, '/' . $otherItem->id), $this->validPayload())->assertStatus(404);

        $this->assertDatabaseHas('invoice_items', ['id' => $otherItem->id, 'description' => 'Other']);
    }

    public function test_edit_item_requires_authentication(): void
    {
        $invoice = $this->createInvoice($this->event);
        $item = $this->createItem($invoice);

        $this->putJson($this->itemsUrl($invoice, '/' . $item->id), $this->validPayload())->assertStatus(401);
        $this->actingAs($this->client, 'client')->putJson($this->itemsUrl($invoice, '/' . $item->id), $this->validPayload())->assertStatus(401);
    }
}
