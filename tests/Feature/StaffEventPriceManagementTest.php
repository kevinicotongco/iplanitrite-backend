<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventPricingFixtures;
use Tests\TestCase;

class StaffEventPriceManagementTest extends TestCase
{
    use CreatesEventPricingFixtures, RefreshDatabase;

    private Event $event;
    private EventPrice $packagePrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventPricingFixtures();

        $this->event = $this->createEvent();
        $this->packagePrice = $this->createPrice($this->event, [
            'name' => 'Gold Package',
            'cost_price' => 0,
            'retail_price' => 50000,
            'sort_order' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Photography',
            'costPrice' => 8000.5,
            'retailPrice' => 12000.25,
            'supplierId' => null,
        ], $overrides);
    }

    private function pricesUrl(?Event $event = null): string
    {
        return '/api/staff/events/' . ($event ?? $this->event)->id . '/prices';
    }

    // GET /api/staff/events/{eventId}/prices

    public function test_staff_can_list_event_prices_ordered_by_sort_order(): void
    {
        $supplier = $this->createSupplier();
        $catering = $this->createPrice($this->event, [
            'name' => 'Catering',
            'cost_price' => 100,
            'retail_price' => 150.5,
            'sort_order' => 2,
            'supplier_id' => $supplier->id,
        ]);
        $flowers = $this->createPrice($this->event, ['name' => 'Flowers', 'sort_order' => 1]);
        $this->createPrice($this->createEvent(), ['name' => 'Other Event Price']);

        $response = $this->withToken($this->token)->getJson($this->pricesUrl());

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonPath('0.id', $this->packagePrice->id)
            ->assertJsonPath('1.id', $flowers->id)
            ->assertJsonPath('2.id', $catering->id)
            ->assertJsonPath('0.supplier', null)
            ->assertJsonPath('2.name', 'Catering')
            ->assertJsonPath('2.costPrice', 100)
            ->assertJsonPath('2.retailPrice', 150.5)
            ->assertJsonPath('2.sortOrder', 2)
            ->assertJsonPath('2.supplier.id', $supplier->id)
            ->assertJsonPath('2.supplier.companyName', 'Acme Catering')
            ->assertJsonStructure([
                '*' => ['id', 'name', 'costPrice', 'retailPrice', 'sortOrder', 'supplier'],
                2 => ['supplier' => ['id', 'companyName', 'contactPerson', 'contactNumber', 'address']],
            ]);
    }

    public function test_list_prices_returns_404_for_other_account_event(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);

        $this->withToken($this->token)->getJson($this->pricesUrl($otherEvent))->assertStatus(404);
    }

    public function test_list_prices_returns_404_for_missing_event(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/staff/events/' . Str::uuid()->toString() . '/prices')
            ->assertStatus(404);

        $this->withToken($this->token)
            ->getJson('/api/staff/events/not-a-uuid/prices')
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_list_prices(): void
    {
        $this->getJson($this->pricesUrl())->assertStatus(401);
    }

    public function test_client_cannot_list_prices(): void
    {
        $this->actingAs($this->client, 'client')->getJson($this->pricesUrl())->assertStatus(401);
    }

    // POST /api/staff/events/{eventId}/prices

    public function test_staff_can_create_price_at_bottom_of_list(): void
    {
        $this->createPrice($this->event, ['sort_order' => 4]);

        $response = $this->withToken($this->token)->postJson($this->pricesUrl(), $this->validPayload());

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Photography',
                'costPrice' => 8000.5,
                'retailPrice' => 12000.25,
                'sortOrder' => 5,
                'supplier' => null,
            ]);

        $this->assertDatabaseHas('event_prices', [
            'id' => $response->json('id'),
            'account_id' => $this->account->id,
            'event_id' => $this->event->id,
            'name' => 'Photography',
            'cost_price' => 8000.5,
            'retail_price' => 12000.25,
            'sort_order' => 5,
            'supplier_id' => null,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_staff_can_create_price_with_supplier(): void
    {
        $supplier = $this->createSupplier();

        $response = $this->withToken($this->token)
            ->postJson($this->pricesUrl(), $this->validPayload(['supplierId' => $supplier->id]));

        $response->assertStatus(201)
            ->assertJsonPath('supplier.id', $supplier->id)
            ->assertJsonPath('supplier.companyName', 'Acme Catering');

        $this->assertDatabaseHas('event_prices', [
            'id' => $response->json('id'),
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_create_price_validates_required_fields(): void
    {
        $this->withToken($this->token)
            ->postJson($this->pricesUrl(), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'costPrice', 'retailPrice']);

        $this->assertDatabaseCount('event_prices', 1);
    }

    public function test_create_price_validates_amounts_and_supplier_format(): void
    {
        $this->withToken($this->token)
            ->postJson($this->pricesUrl(), $this->validPayload([
                'costPrice' => -1,
                'retailPrice' => 1.234,
                'supplierId' => 'not-a-uuid',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['costPrice', 'retailPrice', 'supplierId']);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl(), $this->validPayload(['retailPrice' => 100000000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['retailPrice']);

        $this->assertDatabaseCount('event_prices', 1);
    }

    public function test_create_price_rejects_supplier_from_other_account(): void
    {
        $supplier = $this->createSupplier($this->otherAccount);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl(), $this->validPayload(['supplierId' => $supplier->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);

        $this->assertDatabaseCount('event_prices', 1);
    }

    public function test_create_price_rejects_deleted_or_missing_supplier(): void
    {
        $supplier = $this->createSupplier();
        $supplier->delete();

        $this->withToken($this->token)
            ->postJson($this->pricesUrl(), $this->validPayload(['supplierId' => $supplier->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl(), $this->validPayload(['supplierId' => Str::uuid()->toString()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);

        $this->assertDatabaseCount('event_prices', 1);
    }

    public function test_create_price_returns_404_for_other_account_event(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl($otherEvent), $this->validPayload())
            ->assertStatus(404);

        $this->assertDatabaseMissing('event_prices', ['event_id' => $otherEvent->id]);
    }

    public function test_unauthenticated_user_cannot_create_price(): void
    {
        $this->postJson($this->pricesUrl(), $this->validPayload())->assertStatus(401);

        $this->assertDatabaseCount('event_prices', 1);
    }

    // PUT /api/staff/events/{eventId}/prices/{eventPriceId}

    public function test_staff_can_update_price(): void
    {
        $supplier = $this->createSupplier();
        $price = $this->createPrice($this->event, ['name' => 'Old', 'sort_order' => 3]);

        $response = $this->withToken($this->token)
            ->putJson($this->pricesUrl() . "/{$price->id}", $this->validPayload([
                'name' => 'Videography',
                'supplierId' => $supplier->id,
            ]));

        $response->assertStatus(200)
            ->assertJsonPath('id', $price->id)
            ->assertJsonPath('name', 'Videography')
            ->assertJsonPath('costPrice', 8000.5)
            ->assertJsonPath('retailPrice', 12000.25)
            ->assertJsonPath('sortOrder', 3)
            ->assertJsonPath('supplier.id', $supplier->id);

        $this->assertDatabaseHas('event_prices', [
            'id' => $price->id,
            'name' => 'Videography',
            'cost_price' => 8000.5,
            'retail_price' => 12000.25,
            'sort_order' => 3,
            'supplier_id' => $supplier->id,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_update_price_can_clear_supplier(): void
    {
        $supplier = $this->createSupplier();
        $price = $this->createPrice($this->event, ['supplier_id' => $supplier->id]);

        $this->withToken($this->token)
            ->putJson($this->pricesUrl() . "/{$price->id}", $this->validPayload(['supplierId' => null]))
            ->assertStatus(200)
            ->assertJsonPath('supplier', null);

        $this->assertDatabaseHas('event_prices', ['id' => $price->id, 'supplier_id' => null]);
    }

    public function test_update_price_validates_input(): void
    {
        $price = $this->createPrice($this->event, ['name' => 'Old']);

        $this->withToken($this->token)
            ->putJson($this->pricesUrl() . "/{$price->id}", ['costPrice' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'costPrice', 'retailPrice']);

        $this->assertDatabaseHas('event_prices', ['id' => $price->id, 'name' => 'Old']);
    }

    public function test_update_price_rejects_supplier_from_other_account(): void
    {
        $price = $this->createPrice($this->event, ['name' => 'Old']);
        $supplier = $this->createSupplier($this->otherAccount);

        $this->withToken($this->token)
            ->putJson($this->pricesUrl() . "/{$price->id}", $this->validPayload(['supplierId' => $supplier->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);

        $this->assertDatabaseHas('event_prices', ['id' => $price->id, 'name' => 'Old', 'supplier_id' => null]);
    }

    public function test_update_price_returns_404_for_price_on_another_event(): void
    {
        $otherEventPrice = $this->createPrice($this->createEvent(), ['name' => 'Old']);

        $this->withToken($this->token)
            ->putJson($this->pricesUrl() . "/{$otherEventPrice->id}", $this->validPayload())
            ->assertStatus(404);

        $this->assertDatabaseHas('event_prices', ['id' => $otherEventPrice->id, 'name' => 'Old']);
    }

    public function test_update_price_returns_404_for_other_account_event(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);
        $otherPrice = $this->createPrice($otherEvent, ['name' => 'Old']);

        $this->withToken($this->token)
            ->putJson($this->pricesUrl($otherEvent) . "/{$otherPrice->id}", $this->validPayload())
            ->assertStatus(404);

        $this->assertDatabaseHas('event_prices', ['id' => $otherPrice->id, 'name' => 'Old']);
    }

    public function test_update_price_returns_404_for_missing_price(): void
    {
        $this->withToken($this->token)
            ->putJson($this->pricesUrl() . '/' . Str::uuid()->toString(), $this->validPayload())
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_update_price(): void
    {
        $this->putJson($this->pricesUrl() . "/{$this->packagePrice->id}", $this->validPayload())
            ->assertStatus(401);

        $this->assertDatabaseHas('event_prices', ['id' => $this->packagePrice->id, 'name' => 'Gold Package']);
    }

    // POST /api/staff/events/{eventId}/prices/sort

    public function test_staff_can_sort_prices(): void
    {
        $second = $this->createPrice($this->event, ['sort_order' => 1]);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl() . '/sort', [
                ['id' => $this->packagePrice->id, 'sortOrder' => 1],
                ['id' => $second->id, 'sortOrder' => 0],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_prices', [
            'id' => $this->packagePrice->id,
            'sort_order' => 1,
            'updated_by' => $this->staff->id,
        ]);
        $this->assertDatabaseHas('event_prices', ['id' => $second->id, 'sort_order' => 0]);

        $this->withToken($this->token)
            ->getJson($this->pricesUrl())
            ->assertJsonPath('0.id', $second->id)
            ->assertJsonPath('1.id', $this->packagePrice->id);
    }

    public function test_sort_rolls_back_when_a_price_belongs_to_another_event(): void
    {
        $otherEventPrice = $this->createPrice($this->createEvent(), ['sort_order' => 0]);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl() . '/sort', [
                ['id' => $this->packagePrice->id, 'sortOrder' => 7],
                ['id' => $otherEventPrice->id, 'sortOrder' => 8],
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_prices', ['id' => $this->packagePrice->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('event_prices', ['id' => $otherEventPrice->id, 'sort_order' => 0]);
    }

    public function test_sort_validates_body(): void
    {
        $this->withToken($this->token)
            ->postJson($this->pricesUrl() . '/sort', [])
            ->assertStatus(422);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl() . '/sort', [
                ['id' => $this->packagePrice->id, 'sortOrder' => -1],
                ['id' => 'not-a-uuid'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.sortOrder', '1.id', '1.sortOrder']);

        $this->assertDatabaseHas('event_prices', ['id' => $this->packagePrice->id, 'sort_order' => 0]);
    }

    public function test_sort_returns_404_for_other_account_event(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);
        $otherPrice = $this->createPrice($otherEvent, ['sort_order' => 0]);

        $this->withToken($this->token)
            ->postJson($this->pricesUrl($otherEvent) . '/sort', [['id' => $otherPrice->id, 'sortOrder' => 3]])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_prices', ['id' => $otherPrice->id, 'sort_order' => 0]);
    }

    public function test_unauthenticated_user_cannot_sort_prices(): void
    {
        $this->postJson($this->pricesUrl() . '/sort', [['id' => $this->packagePrice->id, 'sortOrder' => 3]])
            ->assertStatus(401);

        $this->assertDatabaseHas('event_prices', ['id' => $this->packagePrice->id, 'sort_order' => 0]);
    }

    // DELETE /api/staff/events/{eventId}/prices/{eventPriceId}

    public function test_staff_can_delete_price(): void
    {
        $price = $this->createPrice($this->event, ['sort_order' => 1]);

        $this->withToken($this->token)
            ->deleteJson($this->pricesUrl() . "/{$price->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_prices', ['id' => $price->id]);
        $this->assertDatabaseHas('event_prices', ['id' => $this->packagePrice->id]);
    }

    public function test_package_price_cannot_be_deleted(): void
    {
        $this->withToken($this->token)
            ->deleteJson($this->pricesUrl() . "/{$this->packagePrice->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eventPriceId']);

        $this->assertDatabaseHas('event_prices', ['id' => $this->packagePrice->id]);
    }

    public function test_package_price_cannot_be_deleted_after_being_resorted(): void
    {
        $later = $this->createPrice($this->event, ['sort_order' => 0]);
        $this->packagePrice->update(['sort_order' => 9]);

        $this->withToken($this->token)
            ->deleteJson($this->pricesUrl() . "/{$this->packagePrice->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['eventPriceId']);

        $this->withToken($this->token)
            ->deleteJson($this->pricesUrl() . "/{$later->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('event_prices', ['id' => $this->packagePrice->id]);
        $this->assertDatabaseMissing('event_prices', ['id' => $later->id]);
    }

    public function test_delete_price_returns_404_for_price_on_another_event(): void
    {
        $otherEventPrice = $this->createPrice($this->createEvent());

        $this->withToken($this->token)
            ->deleteJson($this->pricesUrl() . "/{$otherEventPrice->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('event_prices', ['id' => $otherEventPrice->id]);
    }

    public function test_delete_price_returns_404_for_other_account_event(): void
    {
        $otherEvent = $this->createEvent(account: $this->otherAccount);
        $this->createPrice($otherEvent);
        $otherPrice = $this->createPrice($otherEvent);

        $this->withToken($this->token)
            ->deleteJson($this->pricesUrl($otherEvent) . "/{$otherPrice->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('event_prices', ['id' => $otherPrice->id]);
    }

    public function test_delete_price_returns_404_for_missing_price(): void
    {
        $this->withToken($this->token)
            ->deleteJson($this->pricesUrl() . '/' . Str::uuid()->toString())
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_price(): void
    {
        $price = $this->createPrice($this->event, ['sort_order' => 1]);

        $this->deleteJson($this->pricesUrl() . "/{$price->id}")->assertStatus(401);

        $this->assertDatabaseHas('event_prices', ['id' => $price->id]);
    }

    public function test_admin_cannot_delete_price(): void
    {
        $price = $this->createPrice($this->event, ['sort_order' => 1]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson($this->pricesUrl() . "/{$price->id}")
            ->assertStatus(401);

        $this->assertDatabaseHas('event_prices', ['id' => $price->id]);
    }
}
