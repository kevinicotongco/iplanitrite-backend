<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventChecklistAssigneeTypeEnum;
use App\Models\EventChecklistAssignee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventChecklistFixtures;
use Tests\TestCase;

class StaffEventChecklistGroupManagementTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEventChecklistFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventChecklistFixtures();
    }

    // GET /api/staff/events/{id}/checklist-groups

    public function test_staff_can_get_checklist_groups_split_by_type(): void
    {
        $secondSupplierGroup = $this->createGroup($this->event, 'Second Supplier Group', ChecklistGroupTypeEnum::Supplier, 0);
        $laterChecklist = $this->createChecklist($this->supplierGroup, 'Later Checklist', 5);
        $this->supplierChecklist->update(['supplier_id' => $this->supplier->id, 'due_date' => '2026-12-01']);

        EventChecklistAssignee::create([
            'event_checklist_id' => $this->supplierChecklist->id,
            'assignee_id' => $this->staff->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Staff,
        ]);
        EventChecklistAssignee::create([
            'event_checklist_id' => $this->supplierChecklist->id,
            'assignee_id' => $this->client->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Client,
        ]);

        $response = $this->withToken($this->token)->getJson($this->groupsUrl());

        $response->assertStatus(200)
            ->assertJsonCount(2, 'supplier')
            ->assertJsonCount(1, 'general')
            ->assertJsonStructure([
                'supplier' => [
                    '*' => [
                        'id',
                        'eventId',
                        'name',
                        'eventType',
                        'checklistType',
                        'sortOrder',
                        'checklists' => [
                            '*' => [
                                'id',
                                'eventChecklistGroupId',
                                'name',
                                'description',
                                'status',
                                'dueDate',
                                'sortOrder',
                                'supplier',
                                'assignees',
                            ],
                        ],
                    ],
                ],
                'general',
            ])
            ->assertJsonPath('supplier.0.id', $secondSupplierGroup->id)
            ->assertJsonPath('supplier.1.id', $this->supplierGroup->id)
            ->assertJsonPath('supplier.1.checklistType', ChecklistGroupTypeEnum::Supplier->value)
            ->assertJsonPath('supplier.1.checklists.0.id', $this->supplierChecklist->id)
            ->assertJsonPath('supplier.1.checklists.0.dueDate', '2026-12-01')
            ->assertJsonPath('supplier.1.checklists.0.supplier.id', $this->supplier->id)
            ->assertJsonPath('supplier.1.checklists.0.supplier.companyName', 'Test Supplier')
            ->assertJsonPath('supplier.1.checklists.1.id', $laterChecklist->id)
            ->assertJsonPath('general.0.id', $this->generalGroup->id)
            ->assertJsonPath('general.0.checklistType', ChecklistGroupTypeEnum::General->value)
            ->assertJsonPath('general.0.checklists.0.supplier', null);

        $assignees = collect($response->json('supplier.1.checklists.0.assignees'))->keyBy('assigneeType');
        $this->assertSame($this->staff->id, $assignees['Staff']['assigneeId']);
        $this->assertSame('Test Staff', $assignees['Staff']['assigneeName']);
        $this->assertSame(self::STAFF_PROFILE_PICTURE, $assignees['Staff']['assigneeProfilePicture']);
        $this->assertSame($this->client->id, $assignees['Client']['assigneeId']);
        $this->assertSame('Test Client', $assignees['Client']['assigneeName']);
        $this->assertNull($assignees['Client']['assigneeProfilePicture']);
    }

    public function test_get_checklist_groups_returns_404_for_event_from_other_account(): void
    {
        $otherEvent = $this->createEvent($this->createAccount('Other Account'));

        $this->withToken($this->token)->getJson($this->groupsUrl($otherEvent))->assertStatus(404);
    }

    public function test_get_checklist_groups_returns_404_for_non_existent_event(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/staff/events/' . Str::uuid()->toString() . '/checklist-groups')
            ->assertStatus(404);
    }

    public function test_get_checklist_groups_returns_404_for_invalid_event_id(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/staff/events/not-a-uuid/checklist-groups')
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_get_checklist_groups(): void
    {
        $this->getJson($this->groupsUrl())->assertStatus(401);
    }

    public function test_client_cannot_get_staff_checklist_groups(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)->getJson($this->groupsUrl())->assertStatus(401);
    }

    // POST /api/staff/events/{id}/checklist-groups

    public function test_staff_can_create_checklist_group_with_last_sort_order_for_type(): void
    {
        $this->createGroup($this->event, 'Supplier Group 2', ChecklistGroupTypeEnum::Supplier, 5);

        $response = $this->withToken($this->token)->postJson($this->groupsUrl(), [
            'name' => 'New General Group',
            'checklistType' => ChecklistGroupTypeEnum::General->value,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_checklist_groups', [
            'event_id' => $this->event->id,
            'name' => 'New General Group',
            'event_type' => $this->event->event_type->value,
            'checklist_type' => ChecklistGroupTypeEnum::General->value,
            'sort_order' => 2,
        ]);
    }

    public function test_create_checklist_group_starts_sort_order_at_one_for_empty_type(): void
    {
        $this->generalChecklist->forceDelete();
        $this->generalGroup->forceDelete();

        $this->withToken($this->token)->postJson($this->groupsUrl(), [
            'name' => 'First General Group',
            'checklistType' => ChecklistGroupTypeEnum::General->value,
        ])->assertStatus(200);

        $this->assertDatabaseHas('event_checklist_groups', [
            'event_id' => $this->event->id,
            'name' => 'First General Group',
            'sort_order' => 1,
        ]);
    }

    public function test_create_checklist_group_writes_audit_log(): void
    {
        $this->withToken($this->token)->postJson($this->groupsUrl(), [
            'name' => 'Logged Group',
            'checklistType' => ChecklistGroupTypeEnum::Supplier->value,
        ])->assertStatus(200);

        $groupId = $this->event->checklistGroups()->where('name', 'Logged Group')->value('id');

        $this->assertDatabaseHas('event_checklist_group_logs', [
            'event_checklist_group_id' => $groupId,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Create->value,
        ]);
    }

    public function test_create_checklist_group_validates_required_fields(): void
    {
        $this->withToken($this->token)
            ->postJson($this->groupsUrl(), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'checklistType']);
    }

    public function test_create_checklist_group_validates_checklist_type(): void
    {
        $this->withToken($this->token)
            ->postJson($this->groupsUrl(), ['name' => 'Group', 'checklistType' => 'Invalid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['checklistType']);
    }

    public function test_create_checklist_group_returns_404_for_event_from_other_account(): void
    {
        $otherEvent = $this->createEvent($this->createAccount('Other Account'));

        $this->withToken($this->token)->postJson($this->groupsUrl($otherEvent), [
            'name' => 'Group',
            'checklistType' => ChecklistGroupTypeEnum::General->value,
        ])->assertStatus(404);

        $this->assertDatabaseMissing('event_checklist_groups', ['event_id' => $otherEvent->id]);
    }

    public function test_unauthenticated_user_cannot_create_checklist_group(): void
    {
        $this->postJson($this->groupsUrl(), [
            'name' => 'Group',
            'checklistType' => ChecklistGroupTypeEnum::General->value,
        ])->assertStatus(401);
    }

    public function test_client_cannot_create_checklist_group(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)->postJson($this->groupsUrl(), [
            'name' => 'Group',
            'checklistType' => ChecklistGroupTypeEnum::General->value,
        ])->assertStatus(401);
    }

    // POST /api/staff/events/{id}/checklist-groups/sort

    public function test_staff_can_sort_checklist_groups(): void
    {
        $response = $this->withToken($this->token)->postJson($this->groupsUrl() . '/sort', [
            ['id' => $this->supplierGroup->id, 'sortOrder' => 3],
            ['id' => $this->generalGroup->id, 'sortOrder' => 4],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_checklist_groups', ['id' => $this->supplierGroup->id, 'sort_order' => 3]);
        $this->assertDatabaseHas('event_checklist_groups', ['id' => $this->generalGroup->id, 'sort_order' => 4]);
        $this->assertDatabaseHas('event_checklist_group_logs', [
            'event_checklist_group_id' => $this->supplierGroup->id,
            'action' => AuditActionEnum::Sort->value,
        ]);
    }

    public function test_sort_checklist_groups_validates_empty_body(): void
    {
        $this->withToken($this->token)
            ->postJson($this->groupsUrl() . '/sort', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_sort_checklist_groups_validates_items(): void
    {
        $this->withToken($this->token)
            ->postJson($this->groupsUrl() . '/sort', [
                ['id' => 'not-a-uuid', 'sortOrder' => 1],
                ['id' => $this->generalGroup->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['0.id', '1.sortOrder']);
    }

    public function test_sort_checklist_groups_returns_404_for_group_from_other_event(): void
    {
        $otherEventGroup = $this->createGroup(
            $this->createEvent($this->account),
            'Other Event Group',
            ChecklistGroupTypeEnum::General,
            1
        );

        $this->withToken($this->token)->postJson($this->groupsUrl() . '/sort', [
            ['id' => $this->supplierGroup->id, 'sortOrder' => 7],
            ['id' => $otherEventGroup->id, 'sortOrder' => 8],
        ])->assertStatus(404);

        $this->assertDatabaseHas('event_checklist_groups', ['id' => $this->supplierGroup->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('event_checklist_groups', ['id' => $otherEventGroup->id, 'sort_order' => 1]);
    }

    public function test_unauthenticated_user_cannot_sort_checklist_groups(): void
    {
        $this->postJson($this->groupsUrl() . '/sort', [
            ['id' => $this->supplierGroup->id, 'sortOrder' => 2],
        ])->assertStatus(401);
    }

    public function test_client_cannot_sort_checklist_groups(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)->postJson($this->groupsUrl() . '/sort', [
            ['id' => $this->supplierGroup->id, 'sortOrder' => 2],
        ])->assertStatus(401);
    }

    // PUT /api/staff/events/{id}/checklist-groups/{groupId}/name

    public function test_staff_can_update_checklist_group_name(): void
    {
        $response = $this->withToken($this->token)
            ->putJson($this->groupsUrl() . '/' . $this->generalGroup->id . '/name', ['name' => 'Renamed Group']);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_checklist_groups', ['id' => $this->generalGroup->id, 'name' => 'Renamed Group']);
        $this->assertDatabaseHas('event_checklist_group_logs', [
            'event_checklist_group_id' => $this->generalGroup->id,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_update_checklist_group_name_validates_name(): void
    {
        $this->withToken($this->token)
            ->putJson($this->groupsUrl() . '/' . $this->generalGroup->id . '/name', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_checklist_group_name_returns_404_for_group_from_other_event(): void
    {
        $otherEventGroup = $this->createGroup(
            $this->createEvent($this->account),
            'Other Event Group',
            ChecklistGroupTypeEnum::General,
            1
        );

        $this->withToken($this->token)
            ->putJson($this->groupsUrl() . '/' . $otherEventGroup->id . '/name', ['name' => 'Hijacked'])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_checklist_groups', ['id' => $otherEventGroup->id, 'name' => 'Other Event Group']);
    }

    public function test_update_checklist_group_name_returns_404_for_event_from_other_account(): void
    {
        $otherEvent = $this->createEvent($this->createAccount('Other Account'));
        $otherGroup = $this->createGroup($otherEvent, 'Other Group', ChecklistGroupTypeEnum::General, 1);

        $this->withToken($this->token)
            ->putJson($this->groupsUrl($otherEvent) . '/' . $otherGroup->id . '/name', ['name' => 'Hijacked'])
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_update_checklist_group_name(): void
    {
        $this->putJson($this->groupsUrl() . '/' . $this->generalGroup->id . '/name', ['name' => 'Renamed'])
            ->assertStatus(401);
    }

    public function test_client_cannot_update_checklist_group_name(): void
    {
        $clientToken = $this->loginClient('client@test.com');

        $this->withToken($clientToken)
            ->putJson($this->groupsUrl() . '/' . $this->generalGroup->id . '/name', ['name' => 'Renamed'])
            ->assertStatus(401);
    }
}
