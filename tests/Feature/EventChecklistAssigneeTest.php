<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\EventChecklistAssigneeTypeEnum;
use App\Enums\EventTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Enums\ResponsibilityTypeEnum;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\AccountTemplateChecklist;
use App\Models\AccountTemplateChecklistGroup;
use App\Models\Client;
use App\Models\Country;
use App\Models\EventChecklistAssignee;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventChecklistAssigneeTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private Account $account;
    private Country $country;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->country = Country::create([
            'id' => Str::uuid()->toString(),
            'name' => 'United States',
            'iso2_code' => 'US',
            'iso3_code' => 'USA',
            'language_locale' => 'en-US',
            'calling_code' => '+1',
            'flag' => '🇺🇸',
            'currency_code' => 'USD',
            'currency_name' => 'US Dollar',
            'currency_symbol' => '$',
        ]);

        $this->account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Account',
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'America/New_York',
        ]);

        $role = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Admin',
        ]);

        $this->staff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $role->id,
            'email' => 'staff@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        $this->token = $this->staff->createToken('staff-token')->plainTextToken;
    }

    public function test_checklist_with_responsibility_staff_assigns_to_staff_only(): void
    {
        $templateGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Staff Task',
            'description' => 'Task for staff only',
            'sort_order' => 1,
            'responsibility_type' => ResponsibilityTypeEnum::Staff->value,
            'frequency_value' => 7,
            'frequency_type' => ChecklistFrequencyTypeEnum::Days->value,
            'frequency_anchor' => FrequencyAnchorEnum::BeforeEvent->value,
        ]);

        $eventData = [
            'name' => 'Test Event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
            'clients' => [
                [
                    'email' => 'client1@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'One',
                ],
                [
                    'email' => 'client2@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'Two',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/events', $eventData);

        $response->assertStatus(201);

        // Should have 1 assignee (staff only)
        $this->assertEquals(1, EventChecklistAssignee::count());

        $this->assertDatabaseHas('event_checklist_assignees', [
            'assignee_id' => $this->staff->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Staff->value,
        ]);

        // Should not assign to clients
        $client1 = Client::where('email', 'client1@example.com')->first();
        $this->assertDatabaseMissing('event_checklist_assignees', [
            'assignee_id' => $client1->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Client->value,
        ]);
    }

    public function test_checklist_with_responsibility_client_assigns_to_all_clients(): void
    {
        $templateGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Client Task',
            'description' => 'Task for clients only',
            'sort_order' => 1,
            'responsibility_type' => ResponsibilityTypeEnum::Client->value,
            'frequency_value' => 7,
            'frequency_type' => ChecklistFrequencyTypeEnum::Days->value,
            'frequency_anchor' => FrequencyAnchorEnum::BeforeEvent->value,
        ]);

        $eventData = [
            'name' => 'Test Event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
            'clients' => [
                [
                    'email' => 'client1@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'One',
                ],
                [
                    'email' => 'client2@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'Two',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/events', $eventData);

        $response->assertStatus(201);

        // Should have 2 assignees (both clients)
        $this->assertEquals(2, EventChecklistAssignee::count());

        $client1 = Client::where('email', 'client1@example.com')->first();
        $client2 = Client::where('email', 'client2@example.com')->first();

        $this->assertDatabaseHas('event_checklist_assignees', [
            'assignee_id' => $client1->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Client->value,
        ]);

        $this->assertDatabaseHas('event_checklist_assignees', [
            'assignee_id' => $client2->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Client->value,
        ]);

        // Should not assign to staff
        $this->assertDatabaseMissing('event_checklist_assignees', [
            'assignee_id' => $this->staff->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Staff->value,
        ]);
    }

    public function test_checklist_with_responsibility_both_assigns_to_staff_and_all_clients(): void
    {
        $templateGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Shared Task',
            'description' => 'Task for both staff and clients',
            'sort_order' => 1,
            'responsibility_type' => ResponsibilityTypeEnum::Both->value,
            'frequency_value' => 7,
            'frequency_type' => ChecklistFrequencyTypeEnum::Days->value,
            'frequency_anchor' => FrequencyAnchorEnum::BeforeEvent->value,
        ]);

        $eventData = [
            'name' => 'Test Event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
            'clients' => [
                [
                    'email' => 'client1@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'One',
                ],
                [
                    'email' => 'client2@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'Two',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/events', $eventData);

        $response->assertStatus(201);

        // Should have 3 assignees (1 staff + 2 clients)
        $this->assertEquals(3, EventChecklistAssignee::count());

        $client1 = Client::where('email', 'client1@example.com')->first();
        $client2 = Client::where('email', 'client2@example.com')->first();

        $this->assertDatabaseHas('event_checklist_assignees', [
            'assignee_id' => $this->staff->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Staff->value,
        ]);

        $this->assertDatabaseHas('event_checklist_assignees', [
            'assignee_id' => $client1->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Client->value,
        ]);

        $this->assertDatabaseHas('event_checklist_assignees', [
            'assignee_id' => $client2->id,
            'assignee_type' => EventChecklistAssigneeTypeEnum::Client->value,
        ]);
    }

    public function test_multiple_checklists_with_different_responsibilities_create_correct_assignees(): void
    {
        $templateGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Staff Task',
            'sort_order' => 1,
            'responsibility_type' => ResponsibilityTypeEnum::Staff->value,
        ]);

        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Client Task',
            'sort_order' => 2,
            'responsibility_type' => ResponsibilityTypeEnum::Client->value,
        ]);

        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Both Task',
            'sort_order' => 3,
            'responsibility_type' => ResponsibilityTypeEnum::Both->value,
        ]);

        $eventData = [
            'name' => 'Test Event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
            'clients' => [
                [
                    'email' => 'client@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'User',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/events', $eventData);

        $response->assertStatus(201);

        // Staff Task: 1 staff
        // Client Task: 1 client
        // Both Task: 1 staff + 1 client
        // Total: 5 assignees
        $this->assertEquals(5, EventChecklistAssignee::count());
    }

    public function test_checklist_without_responsibility_type_creates_no_assignees(): void
    {
        $templateGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
        ]);

        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $templateGroup->id,
            'name' => 'Unassigned Task',
            'sort_order' => 1,
            'responsibility_type' => null,
        ]);

        $eventData = [
            'name' => 'Test Event',
            'eventType' => EventTypeEnum::Birthday->value,
            'celebrant' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
            'segment' => [
                'date' => '2024-12-25',
                'startTime' => '14:00:00',
                'endTime' => '18:00:00',
                'address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                ],
            ],
            'clients' => [
                [
                    'email' => 'client@example.com',
                    'firstName' => 'Client',
                    'lastName' => 'User',
                ],
            ],
        ];

        $response = $this->withToken($this->token)
            ->postJson('/api/staff/events', $eventData);

        $response->assertStatus(201);

        // Should have 0 assignees
        $this->assertEquals(0, EventChecklistAssignee::count());
    }
}
