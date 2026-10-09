<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Enums\EventChecklistStatusEnum;
use App\Models\Event;
use App\Models\EventChecklistLog;
use App\Models\Supplier;
use App\Models\SupplierLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventChecklistFixtures;
use Tests\TestCase;

class ClientEventChecklistTest extends TestCase
{
    use CreatesEventChecklistFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventChecklistFixtures();
    }

    private function baseUrl(?Event $event = null): string
    {
        return '/api/clients/events/' . ($event ?? $this->event)->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function supplierPayload(): array
    {
        return [
            'companyName' => 'Client Supplier',
            'contactPerson' => 'Jane Doe',
            'contactNumber' => '+639171234567',
            'address' => [
                'line1' => '1 Test St',
                'city' => 'Test City',
                'state' => 'Test State',
                'zip' => '12345',
            ],
        ];
    }

    private function otherEvent(): Event
    {
        return $this->createEvent($this->account);
    }

    // GET checklist-groups

    public function test_client_can_list_checklist_groups_by_type(): void
    {
        $this->actingAs($this->client, 'client')->getJson($this->baseUrl() . '/checklist-groups')
            ->assertStatus(200)
            ->assertJsonCount(1, 'supplier')
            ->assertJsonCount(1, 'general')
            ->assertJsonPath('supplier.0.id', $this->supplierGroup->id)
            ->assertJsonPath('general.0.id', $this->generalGroup->id);
    }

    public function test_list_checklist_groups_returns_404_for_event_client_is_not_linked_to(): void
    {
        $this->actingAs($this->client, 'client')->getJson($this->baseUrl($this->otherEvent()) . '/checklist-groups')
            ->assertStatus(404);
    }

    public function test_list_checklist_groups_returns_404_for_missing_event(): void
    {
        $this->actingAs($this->client, 'client')
            ->getJson('/api/clients/events/' . Str::uuid()->toString() . '/checklist-groups')
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_list_checklist_groups(): void
    {
        $this->getJson($this->baseUrl() . '/checklist-groups')->assertStatus(401);
    }

    public function test_staff_cannot_list_checklist_groups_through_client_route(): void
    {
        $this->withToken($this->token)->getJson($this->baseUrl() . '/checklist-groups')->assertStatus(401);
    }

    // PATCH checklists/{id}/status

    public function test_client_can_update_checklist_status(): void
    {
        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->generalChecklist->id . '/status', [
                'status' => EventChecklistStatusEnum::Completed->value,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', [
            'id' => $this->generalChecklist->id,
            'status' => EventChecklistStatusEnum::Completed->value,
        ]);
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $this->generalChecklist->id,
            'audit_by' => $this->client->id,
            'audit_type' => AuditTypeEnum::Client->value,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_update_checklist_status_validates_status(): void
    {
        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->generalChecklist->id . '/status', ['status' => 'Nope'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->generalChecklist->id . '/status', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_checklist_status_returns_404_for_missing_checklist(): void
    {
        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . Str::uuid()->toString() . '/status', [
                'status' => EventChecklistStatusEnum::Completed->value,
            ])
            ->assertStatus(404);
    }

    public function test_update_checklist_status_returns_404_for_checklist_of_another_event(): void
    {
        $otherEvent = $this->otherEvent();
        $this->attachClientToEvent($otherEvent, $this->client);

        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl($otherEvent) . '/checklists/' . $this->generalChecklist->id . '/status', [
                'status' => EventChecklistStatusEnum::Completed->value,
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_checklists', [
            'id' => $this->generalChecklist->id,
            'status' => EventChecklistStatusEnum::Pending->value,
        ]);
    }

    public function test_update_checklist_status_returns_404_for_event_client_is_not_linked_to(): void
    {
        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl($this->otherEvent()) . '/checklists/' . $this->generalChecklist->id . '/status', [
                'status' => EventChecklistStatusEnum::Completed->value,
            ])
            ->assertStatus(404);
    }

    public function test_unauthenticated_and_staff_cannot_update_checklist_status(): void
    {
        $url = $this->baseUrl() . '/checklists/' . $this->generalChecklist->id . '/status';
        $payload = ['status' => EventChecklistStatusEnum::Completed->value];

        $this->patchJson($url, $payload)->assertStatus(401);
        $this->withToken($this->token)->patchJson($url, $payload)->assertStatus(401);

        $this->assertSame(0, EventChecklistLog::count());
    }

    // PATCH checklists/{id}/supplier

    public function test_client_can_assign_supplier_to_supplier_checklist(): void
    {
        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->supplierChecklist->id . '/supplier', [
                'supplierId' => $this->supplier->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('id', $this->supplier->id)
            ->assertJsonPath('companyName', 'Test Supplier');

        $this->assertDatabaseHas('event_checklists', [
            'id' => $this->supplierChecklist->id,
            'supplier_id' => $this->supplier->id,
        ]);
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $this->supplierChecklist->id,
            'audit_type' => AuditTypeEnum::Client->value,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_client_can_unassign_supplier_with_null(): void
    {
        $this->supplierChecklist->update(['supplier_id' => $this->supplier->id]);

        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->supplierChecklist->id . '/supplier', ['supplierId' => null])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', [
            'id' => $this->supplierChecklist->id,
            'supplier_id' => null,
        ]);
    }

    public function test_assign_supplier_validates_body(): void
    {
        $url = $this->baseUrl() . '/checklists/' . $this->supplierChecklist->id . '/supplier';

        $this->actingAs($this->client, 'client')->patchJson($url, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);

        $this->actingAs($this->client, 'client')->patchJson($url, ['supplierId' => 'not-a-uuid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);
    }

    public function test_assign_supplier_rejects_general_checklist(): void
    {
        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->generalChecklist->id . '/supplier', [
                'supplierId' => $this->supplier->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->generalChecklist->id, 'supplier_id' => null]);
    }

    public function test_assign_supplier_rejects_unknown_supplier(): void
    {
        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->supplierChecklist->id . '/supplier', [
                'supplierId' => Str::uuid()->toString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);
    }

    public function test_assign_supplier_rejects_supplier_of_another_account(): void
    {
        $otherSupplier = $this->createSupplier($this->createAccount('Other Account'), 'Other Supplier');

        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->supplierChecklist->id . '/supplier', [
                'supplierId' => $otherSupplier->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->supplierChecklist->id, 'supplier_id' => null]);
    }

    public function test_assign_supplier_rejects_soft_deleted_supplier(): void
    {
        $this->supplier->delete();

        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . $this->supplierChecklist->id . '/supplier', [
                'supplierId' => $this->supplier->id,
            ])
            ->assertStatus(422);
    }

    public function test_assign_supplier_returns_404_for_missing_checklist_and_unlinked_event(): void
    {
        $payload = ['supplierId' => $this->supplier->id];

        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl() . '/checklists/' . Str::uuid()->toString() . '/supplier', $payload)
            ->assertStatus(404);

        $this->actingAs($this->client, 'client')
            ->patchJson($this->baseUrl($this->otherEvent()) . '/checklists/' . $this->supplierChecklist->id . '/supplier', $payload)
            ->assertStatus(404);
    }

    public function test_unauthenticated_and_staff_cannot_assign_supplier(): void
    {
        $url = $this->baseUrl() . '/checklists/' . $this->supplierChecklist->id . '/supplier';
        $payload = ['supplierId' => $this->supplier->id];

        $this->patchJson($url, $payload)->assertStatus(401);
        $this->withToken($this->token)->patchJson($url, $payload)->assertStatus(401);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->supplierChecklist->id, 'supplier_id' => null]);
    }

    // POST suppliers

    public function test_client_can_create_supplier_for_event_account(): void
    {
        $this->actingAs($this->client, 'client')
            ->postJson($this->baseUrl() . '/suppliers', $this->supplierPayload())
            ->assertStatus(200)
            ->assertJsonPath('companyName', 'Client Supplier')
            ->assertJsonPath('contactPerson', 'Jane Doe')
            ->assertJsonStructure(['id', 'companyName', 'contactPerson', 'contactNumber', 'address']);

        $supplier = Supplier::where('company_name', 'Client Supplier')->firstOrFail();

        $this->assertSame($this->account->id, $supplier->account_id);
        $this->assertDatabaseHas('supplier_logs', [
            'supplier_id' => $supplier->id,
            'audit_by' => $this->client->id,
            'audit_type' => AuditTypeEnum::Client->value,
            'action' => AuditActionEnum::Create->value,
        ]);
        $this->assertSame(1, SupplierLog::where('supplier_id', $supplier->id)->count());
    }

    public function test_create_supplier_validates_body(): void
    {
        $this->actingAs($this->client, 'client')
            ->postJson($this->baseUrl() . '/suppliers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['companyName', 'contactPerson', 'contactNumber', 'address']);

        $this->assertSame(1, Supplier::count());
    }

    public function test_create_supplier_returns_404_for_event_client_is_not_linked_to(): void
    {
        $this->actingAs($this->client, 'client')
            ->postJson($this->baseUrl($this->otherEvent()) . '/suppliers', $this->supplierPayload())
            ->assertStatus(404);

        $this->assertSame(1, Supplier::count());
    }

    public function test_create_supplier_returns_404_for_missing_event(): void
    {
        $this->actingAs($this->client, 'client')
            ->postJson('/api/clients/events/' . Str::uuid()->toString() . '/suppliers', $this->supplierPayload())
            ->assertStatus(404);
    }

    public function test_unauthenticated_and_staff_cannot_create_supplier(): void
    {
        $this->postJson($this->baseUrl() . '/suppliers', $this->supplierPayload())->assertStatus(401);
        $this->withToken($this->token)->postJson($this->baseUrl() . '/suppliers', $this->supplierPayload())->assertStatus(401);

        $this->assertSame(1, Supplier::count());
    }
}
