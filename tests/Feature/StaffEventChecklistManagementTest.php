<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditActionEnum;
use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventChecklistAssigneeTypeEnum;
use App\Enums\EventChecklistStatusEnum;
use App\Models\EventChecklist;
use App\Models\EventChecklistAssignee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventChecklistFixtures;
use Tests\TestCase;

class StaffEventChecklistManagementTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEventChecklistFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventChecklistFixtures();
    }

    private function checklistUrl(EventChecklist $checklist, string $suffix): string
    {
        return $this->checklistsUrl($checklist->group) . '/' . $checklist->id . '/' . $suffix;
    }

    // POST /api/staff/events/{id}/checklist-groups/{groupId}/checklists

    public function test_staff_can_create_checklist_with_last_sort_order_in_group(): void
    {
        $this->createChecklist($this->generalGroup, 'Another Checklist', 4);

        $response = $this->withToken($this->token)
            ->postJson($this->checklistsUrl($this->generalGroup), ['name' => 'New Checklist']);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', [
            'event_checklist_group_id' => $this->generalGroup->id,
            'name' => 'New Checklist',
            'status' => EventChecklistStatusEnum::Pending->value,
            'sort_order' => 5,
            'supplier_id' => null,
            'due_date' => null,
        ]);

        $checklistId = EventChecklist::where('name', 'New Checklist')->value('id');
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $checklistId,
            'audit_by' => $this->staff->id,
            'action' => AuditActionEnum::Create->value,
        ]);
    }

    public function test_create_checklist_sort_order_is_scoped_to_group(): void
    {
        $this->createChecklist($this->supplierGroup, 'Supplier Checklist 2', 9);
        $emptyGroup = $this->createGroup($this->event, 'Empty Group', ChecklistGroupTypeEnum::General, 2);

        $this->withToken($this->token)
            ->postJson($this->checklistsUrl($emptyGroup), ['name' => 'First In Group'])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', [
            'event_checklist_group_id' => $emptyGroup->id,
            'name' => 'First In Group',
            'sort_order' => 1,
        ]);
    }

    public function test_create_checklist_validates_name(): void
    {
        $this->withToken($this->token)
            ->postJson($this->checklistsUrl($this->generalGroup), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_checklist_returns_404_for_group_from_other_event(): void
    {
        $otherEventGroup = $this->createGroup(
            $this->createEvent($this->account),
            'Other Event Group',
            ChecklistGroupTypeEnum::General,
            1
        );

        $this->withToken($this->token)
            ->postJson($this->checklistsUrl($otherEventGroup), ['name' => 'New Checklist'])
            ->assertStatus(404);

        $this->assertDatabaseMissing('event_checklists', ['event_checklist_group_id' => $otherEventGroup->id]);
    }

    public function test_create_checklist_returns_404_for_event_from_other_account(): void
    {
        $otherEvent = $this->createEvent($this->createAccount('Other Account'));
        $otherGroup = $this->createGroup($otherEvent, 'Other Group', ChecklistGroupTypeEnum::General, 1);

        $this->withToken($this->token)
            ->postJson($this->checklistsUrl($otherGroup, $otherEvent), ['name' => 'New Checklist'])
            ->assertStatus(404);
    }

    public function test_create_checklist_returns_404_for_non_existent_group(): void
    {
        $this->withToken($this->token)
            ->postJson($this->groupsUrl() . '/' . Str::uuid()->toString() . '/checklists', ['name' => 'New Checklist'])
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_create_checklist(): void
    {
        $this->postJson($this->checklistsUrl($this->generalGroup), ['name' => 'New Checklist'])
            ->assertStatus(401);
    }

    public function test_client_cannot_create_checklist(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)
            ->postJson($this->checklistsUrl($this->generalGroup), ['name' => 'New Checklist'])
            ->assertStatus(401);
    }

    // POST /api/staff/events/{id}/checklist-groups/{groupId}/checklists/sort

    public function test_staff_can_sort_checklists(): void
    {
        $secondChecklist = $this->createChecklist($this->generalGroup, 'Second Checklist', 2);

        $response = $this->withToken($this->token)
            ->postJson($this->checklistsUrl($this->generalGroup) . '/sort', [
                ['id' => $this->generalChecklist->id, 'sortOrder' => 2],
                ['id' => $secondChecklist->id, 'sortOrder' => 1],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->generalChecklist->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('event_checklists', ['id' => $secondChecklist->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $secondChecklist->id,
            'action' => AuditActionEnum::Sort->value,
        ]);
    }

    public function test_sort_checklists_validates_body(): void
    {
        $this->withToken($this->token)
            ->postJson($this->checklistsUrl($this->generalGroup) . '/sort', [
                ['id' => $this->generalChecklist->id, 'sortOrder' => 'first'],
                ['id' => $this->generalChecklist->id, 'sortOrder' => 2],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.sortOrder', '0.id']);
    }

    public function test_sort_checklists_returns_404_for_checklist_from_other_group(): void
    {
        $this->withToken($this->token)
            ->postJson($this->checklistsUrl($this->generalGroup) . '/sort', [
                ['id' => $this->generalChecklist->id, 'sortOrder' => 6],
                ['id' => $this->supplierChecklist->id, 'sortOrder' => 7],
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->generalChecklist->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('event_checklists', ['id' => $this->supplierChecklist->id, 'sort_order' => 1]);
    }

    public function test_sort_checklists_returns_404_for_group_from_other_event(): void
    {
        $otherEventGroup = $this->createGroup(
            $this->createEvent($this->account),
            'Other Event Group',
            ChecklistGroupTypeEnum::General,
            1
        );
        $otherChecklist = $this->createChecklist($otherEventGroup, 'Other Checklist', 1);

        $this->withToken($this->token)
            ->postJson($this->checklistsUrl($otherEventGroup) . '/sort', [
                ['id' => $otherChecklist->id, 'sortOrder' => 3],
            ])
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_sort_checklists(): void
    {
        $this->postJson($this->checklistsUrl($this->generalGroup) . '/sort', [
            ['id' => $this->generalChecklist->id, 'sortOrder' => 2],
        ])->assertStatus(401);
    }

    public function test_client_cannot_sort_checklists(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)->postJson($this->checklistsUrl($this->generalGroup) . '/sort', [
            ['id' => $this->generalChecklist->id, 'sortOrder' => 2],
        ])->assertStatus(401);
    }

    // PUT .../checklists/{checklistId}/name

    public function test_staff_can_update_checklist_name(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'name'), ['name' => 'Renamed Checklist'])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->generalChecklist->id, 'name' => 'Renamed Checklist']);
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $this->generalChecklist->id,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_update_checklist_name_validates_name(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'name'), ['name' => str_repeat('a', 256)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_checklist_name_returns_404_for_checklist_from_other_group(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistsUrl($this->generalGroup) . '/' . $this->supplierChecklist->id . '/name', ['name' => 'Hijacked'])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->supplierChecklist->id, 'name' => 'Supplier Checklist']);
    }

    public function test_unauthenticated_user_cannot_update_checklist_name(): void
    {
        $this->putJson($this->checklistUrl($this->generalChecklist, 'name'), ['name' => 'Renamed'])
            ->assertStatus(401);
    }

    public function test_client_cannot_update_checklist_name(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)
            ->putJson($this->checklistUrl($this->generalChecklist, 'name'), ['name' => 'Renamed'])
            ->assertStatus(401);
    }

    // PUT .../checklists/{checklistId}/due-date

    public function test_staff_can_update_checklist_due_date(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'due-date'), ['dueDate' => '2026-11-15'])
            ->assertStatus(200);

        $this->assertSame('2026-11-15', $this->generalChecklist->fresh()->due_date->format('Y-m-d'));
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $this->generalChecklist->id,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_staff_can_clear_checklist_due_date(): void
    {
        $this->generalChecklist->update(['due_date' => '2026-11-15']);

        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'due-date'), ['dueDate' => null])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->generalChecklist->id, 'due_date' => null]);
    }

    public function test_update_checklist_due_date_validates_format(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'due-date'), ['dueDate' => '15/11/2026'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dueDate']);
    }

    public function test_update_checklist_due_date_requires_due_date_key(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'due-date'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dueDate']);
    }

    public function test_update_checklist_due_date_returns_404_for_non_existent_checklist(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistsUrl($this->generalGroup) . '/' . Str::uuid()->toString() . '/due-date', ['dueDate' => '2026-11-15'])
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_update_checklist_due_date(): void
    {
        $this->putJson($this->checklistUrl($this->generalChecklist, 'due-date'), ['dueDate' => '2026-11-15'])
            ->assertStatus(401);
    }

    public function test_client_cannot_update_checklist_due_date(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)
            ->putJson($this->checklistUrl($this->generalChecklist, 'due-date'), ['dueDate' => '2026-11-15'])
            ->assertStatus(401);
    }

    // PUT .../checklists/{checklistId}/status

    public function test_staff_can_update_checklist_status(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'status'), ['status' => EventChecklistStatusEnum::Completed->value])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_checklists', [
            'id' => $this->generalChecklist->id,
            'status' => EventChecklistStatusEnum::Completed->value,
        ]);
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $this->generalChecklist->id,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_update_checklist_status_rejects_event_status_values(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'status'), ['status' => 'Ongoing'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_checklist_status_returns_404_for_event_from_other_account(): void
    {
        $otherEvent = $this->createEvent($this->createAccount('Other Account'));
        $otherGroup = $this->createGroup($otherEvent, 'Other Group', ChecklistGroupTypeEnum::General, 1);
        $otherChecklist = $this->createChecklist($otherGroup, 'Other Checklist', 1);

        $this->withToken($this->token)
            ->putJson(
                $this->checklistsUrl($otherGroup, $otherEvent) . '/' . $otherChecklist->id . '/status',
                ['status' => EventChecklistStatusEnum::Completed->value]
            )
            ->assertStatus(404);

        $this->assertDatabaseHas('event_checklists', [
            'id' => $otherChecklist->id,
            'status' => EventChecklistStatusEnum::Pending->value,
        ]);
    }

    public function test_unauthenticated_user_cannot_update_checklist_status(): void
    {
        $this->putJson($this->checklistUrl($this->generalChecklist, 'status'), ['status' => EventChecklistStatusEnum::Completed->value])
            ->assertStatus(401);
    }

    public function test_client_cannot_update_checklist_status(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)
            ->putJson($this->checklistUrl($this->generalChecklist, 'status'), ['status' => EventChecklistStatusEnum::Completed->value])
            ->assertStatus(401);
    }

    // PUT .../checklists/{checklistId}/assignee

    public function test_staff_can_replace_checklist_assignees(): void
    {
        $oldAssignee = EventChecklistAssignee::create([
            'event_checklist_id' => $this->generalChecklist->id,
            'assignee_id' => $this->staff->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Staff,
        ]);

        $response = $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [
                ['assigneeId' => $this->client->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Client->value],
            ]);

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.assigneeId', $this->client->id)
            ->assertJsonPath('0.assigneeType', EventChecklistAssigneeTypeEnum::Client->value)
            ->assertJsonPath('0.assigneeName', 'Test Client')
            ->assertJsonPath('0.assigneeProfilePicture', null);

        $this->assertDatabaseMissing('event_checklist_assignees', ['id' => $oldAssignee->id]);
        $this->assertDatabaseHas('event_checklist_assignees', [
            'event_checklist_id' => $this->generalChecklist->id,
            'assignee_id' => $this->client->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Client->value,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $this->generalChecklist->id,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_replace_checklist_assignees_returns_staff_profile_picture(): void
    {
        $response = $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [
                ['assigneeId' => $this->staff->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Staff->value],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('0.assigneeName', 'Test Staff')
            ->assertJsonPath('0.assigneeProfilePicture', self::STAFF_PROFILE_PICTURE)
            ->assertJsonStructure([
                '*' => ['assigneeId', 'assigneeType', 'assigneeName', 'assigneeProfilePicture'],
            ]);
    }

    public function test_empty_assignee_list_clears_all_assignees(): void
    {
        EventChecklistAssignee::create([
            'event_checklist_id' => $this->generalChecklist->id,
            'assignee_id' => $this->staff->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Staff,
        ]);

        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [])
            ->assertStatus(200)
            ->assertExactJson([]);

        $this->assertSame(
            0,
            EventChecklistAssignee::withTrashed()->where('event_checklist_id', $this->generalChecklist->id)->count()
        );
    }

    public function test_replace_assignees_rejects_staff_from_other_account(): void
    {
        $otherStaff = $this->createStaff($this->createAccount('Other Account'), 'other-staff@test.com');

        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [
                ['assigneeId' => $otherStaff->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Staff->value],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.assigneeId']);

        $this->assertDatabaseMissing('event_checklist_assignees', ['assignee_id' => $otherStaff->id]);
    }

    public function test_replace_assignees_rejects_client_not_attached_to_event(): void
    {
        $unattachedClient = $this->createClient($this->account, 'unattached@test.com');

        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [
                ['assigneeId' => $this->client->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Client->value],
                ['assigneeId' => $unattachedClient->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Client->value],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['1.assigneeId'])
            ->assertJsonMissingValidationErrors(['0.assigneeId']);

        $this->assertDatabaseMissing('event_checklist_assignees', ['assignee_id' => $this->client->id]);
    }

    public function test_replace_assignees_rejects_duplicates(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [
                ['assigneeId' => $this->staff->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Staff->value],
                ['assigneeId' => $this->staff->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Staff->value],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['1.assigneeId']);
    }

    public function test_replace_assignees_validates_items(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [
                ['assigneeId' => 'not-a-uuid', 'assigneeType' => 'Admin'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.assigneeId', '0.assigneeType']);
    }

    public function test_replace_assignees_returns_404_for_checklist_from_other_group(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistsUrl($this->generalGroup) . '/' . $this->supplierChecklist->id . '/assignee', [
                ['assigneeId' => $this->staff->id, 'assigneeType' => EventChecklistAssigneeTypeEnum::Staff->value],
            ])
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_replace_assignees(): void
    {
        $this->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [])
            ->assertStatus(401);
    }

    public function test_client_cannot_replace_assignees(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)
            ->putJson($this->checklistUrl($this->generalChecklist, 'assignee'), [])
            ->assertStatus(401);
    }

    // PUT .../checklists/{checklistId}/supplier

    public function test_staff_can_update_checklist_supplier(): void
    {
        $response = $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->supplierChecklist, 'supplier'), ['supplierId' => $this->supplier->id]);

        $response->assertStatus(200)
            ->assertJsonPath('id', $this->supplier->id)
            ->assertJsonPath('companyName', 'Test Supplier')
            ->assertJsonStructure([
                'id',
                'companyName',
                'contactPerson',
                'contactNumber',
                'address' => ['country'],
            ]);

        $this->assertDatabaseHas('event_checklists', [
            'id' => $this->supplierChecklist->id,
            'supplier_id' => $this->supplier->id,
        ]);
        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $this->supplierChecklist->id,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_update_supplier_on_general_group_checklist_returns_server_error(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->generalChecklist, 'supplier'), ['supplierId' => $this->supplier->id])
            ->assertStatus(500);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->generalChecklist->id, 'supplier_id' => null]);
    }

    public function test_update_supplier_returns_404_for_supplier_from_other_account(): void
    {
        $otherSupplier = $this->createSupplier($this->createAccount('Other Account'), 'Other Supplier');

        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->supplierChecklist, 'supplier'), ['supplierId' => $otherSupplier->id])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_checklists', ['id' => $this->supplierChecklist->id, 'supplier_id' => null]);
    }

    public function test_update_supplier_returns_404_for_non_existent_supplier(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->supplierChecklist, 'supplier'), ['supplierId' => Str::uuid()->toString()])
            ->assertStatus(404);
    }

    public function test_update_supplier_validates_supplier_id(): void
    {
        $this->withToken($this->token)
            ->putJson($this->checklistUrl($this->supplierChecklist, 'supplier'), ['supplierId' => 'not-a-uuid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supplierId']);
    }

    public function test_unauthenticated_user_cannot_update_checklist_supplier(): void
    {
        $this->putJson($this->checklistUrl($this->supplierChecklist, 'supplier'), ['supplierId' => $this->supplier->id])
            ->assertStatus(401);
    }

    public function test_client_cannot_update_checklist_supplier(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)
            ->putJson($this->checklistUrl($this->supplierChecklist, 'supplier'), ['supplierId' => $this->supplier->id])
            ->assertStatus(401);
    }
}
