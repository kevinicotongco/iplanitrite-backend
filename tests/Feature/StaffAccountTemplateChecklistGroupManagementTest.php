<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountRole;
use App\Models\AccountTemplateChecklistGroup;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffAccountTemplateChecklistGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private Staff $staff;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $country = $this->createTestCountry();

        $this->account = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Test Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Test Description',
            'country_id' => $country->id,
            'timezone' => 'UTC',
        ]);

        $accountRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Manager',
        ]);

        $this->staff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'account_role_id' => $accountRole->id,
            'email' => 'staff@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
        ]);

        // Login via API to get a real token
        $response = $this->postJson('/api/staff/login', [
            'email' => 'staff@test.com',
            'password' => 'password123',
        ]);

        $this->token = $response->json('token');
    }

    public function test_staff_can_get_checklist_groups_by_type_and_event_type(): void
    {
        AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Supplier Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'Supplier',
            'sort_order' => 1,
        ]);

        AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'General Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/staff/account-template-checklist-groups/Supplier/Wedding');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Supplier Group']);
    }

    public function test_staff_can_create_checklist_group(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/General/Wedding', [
                'name' => 'New Group',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklist_groups', [
            'account_id' => $this->account->id,
            'name' => 'New Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);
    }

    public function test_new_group_sort_order_is_auto_incremented(): void
    {
        AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'First Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/General/Wedding', [
                'name' => 'Second Group',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklist_groups', [
            'name' => 'Second Group',
            'sort_order' => 2,
        ]);
    }

    public function test_staff_can_update_checklist_group_name(): void
    {
        $group = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Original Name',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $group->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklist_groups', [
            'id' => $group->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_staff_can_bulk_update_group_sort_order(): void
    {
        $group1 = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Group 1',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $group2 = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Group 2',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 2,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/sort', [
                ['id' => $group1->id, 'sortOrder' => 2],
                ['id' => $group2->id, 'sortOrder' => 1],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklist_groups', [
            'id' => $group1->id,
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('account_template_checklist_groups', [
            'id' => $group2->id,
            'sort_order' => 1,
        ]);
    }

    public function test_staff_can_delete_checklist_group(): void
    {
        $group = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/staff/account-template-checklist-groups/' . $group->id);

        $response->assertStatus(200);

        $this->assertSoftDeleted('account_template_checklist_groups', [
            'id' => $group->id,
        ]);
    }

    public function test_create_group_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/General/Wedding', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_staff_cannot_access_groups(): void
    {
        $response = $this->getJson('/api/staff/account-template-checklist-groups/General/Wedding');
        $response->assertStatus(401);
    }

    public function test_staff_cannot_view_groups_from_other_account(): void
    {
        $country = $this->createTestCountry();
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Other Description',
            'country_id' => $country->id,
            'timezone' => 'UTC',
        ]);

        $otherRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Manager',
        ]);

        $otherStaff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'account_role_id' => $otherRole->id,
            'email' => 'otherstaff@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Other',
            'last_name' => 'Staff',
        ]);

        AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Other Account Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/staff/account-template-checklist-groups/General/Wedding');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_staff_cannot_update_group_from_other_account(): void
    {
        $country = $this->createTestCountry();
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Other Description',
            'country_id' => $country->id,
            'timezone' => 'UTC',
        ]);

        $otherRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Manager',
        ]);

        $otherStaff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'account_role_id' => $otherRole->id,
            'email' => 'otherstaff@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Other',
            'last_name' => 'Staff',
        ]);

        $otherGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Other Account Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $otherGroup->id, [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(404);
    }

    public function test_staff_cannot_delete_group_from_other_account(): void
    {
        $country = $this->createTestCountry();
        $otherAccount = Account::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Other Account',
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Other Description',
            'country_id' => $country->id,
            'timezone' => 'UTC',
        ]);

        $otherRole = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Manager',
        ]);

        $otherStaff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'account_role_id' => $otherRole->id,
            'email' => 'otherstaff@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Other',
            'last_name' => 'Staff',
        ]);

        $otherGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'name' => 'Other Account Group',
            'event_type' => 'Wedding',
            'checklist_type' => 'General',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/staff/account-template-checklist-groups/' . $otherGroup->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('account_template_checklist_groups', [
            'id' => $otherGroup->id,
            'deleted_at' => null,
        ]);
    }
}
