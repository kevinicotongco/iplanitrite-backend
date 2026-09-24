<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChecklistGroupTypeEnum;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\AccountTemplateChecklist;
use App\Models\AccountTemplateChecklistGroup;
use App\Models\Address;
use App\Models\ContactNumber;
use App\Models\Staff;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffAccountTemplateChecklistManagementTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private Staff $staff;
    private string $token;
    private AccountTemplateChecklistGroup $group;
    private Supplier $supplier;

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

        $this->group = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Group',
            'event_type' => 'Wedding',
            'checklist_type' => ChecklistGroupTypeEnum::General->value,
            'sort_order' => 1,
        ]);

        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $country->id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $country->id,
        ]);

        $this->supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'company_name' => 'Test Supplier',
            'contact_person' => 'John Doe',
            'contact_number_id' => $contactNumber->id,
            'address_id' => $address->id,
        ]);

        // Login via API to get a real token
        $response = $this->postJson('/api/staff/login', [
            'email' => 'staff@test.com',
            'password' => 'password123',
        ]);

        $this->token = $response->json('token');
    }

    public function test_staff_can_create_checklist(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists', [
                'name' => 'New Checklist',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklists', [
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'New Checklist',
            'sort_order' => 1,
        ]);
    }

    public function test_new_checklist_sort_order_is_auto_incremented(): void
    {
        AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'First Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists', [
                'name' => 'Second Checklist',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklists', [
            'name' => 'Second Checklist',
            'sort_order' => 2,
        ]);
    }

    public function test_staff_can_update_checklist_name(): void
    {
        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'Original Name',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists/' . $checklist->id . '/name', [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklists', [
            'id' => $checklist->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_staff_can_update_checklist_frequency(): void
    {
        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'Test Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists/' . $checklist->id . '/frequency', [
                'frequencyType' => 'Weeks',
                'frequencyAnchor' => 'BeforeEvent',
                'frequencyValue' => 2,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklists', [
            'id' => $checklist->id,
            'frequency_type' => 'Weeks',
            'frequency_anchor' => 'BeforeEvent',
            'frequency_value' => 2,
        ]);
    }

    public function test_staff_can_update_checklist_responsibility(): void
    {
        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'Test Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists/' . $checklist->id . '/responsibility', [
                'responsibilityType' => 'Client',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklists', [
            'id' => $checklist->id,
            'responsibility_type' => 'Client',
        ]);
    }

    public function test_staff_can_update_checklist_supplier(): void
    {
        // Create a Supplier type group
        $supplierGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Supplier Group',
            'event_type' => 'Wedding',
            'checklist_type' => ChecklistGroupTypeEnum::Supplier->value,
            'sort_order' => 2,
        ]);

        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $supplierGroup->id,
            'name' => 'Test Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $supplierGroup->id . '/account-template-checklists/' . $checklist->id . '/supplier', [
                'supplierId' => $this->supplier->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklists', [
            'id' => $checklist->id,
            'supplier_id' => $this->supplier->id,
        ]);
    }

    public function test_supplier_can_only_be_assigned_to_supplier_checklist_groups(): void
    {
        $generalGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'General Group',
            'event_type' => 'Wedding',
            'checklist_type' => ChecklistGroupTypeEnum::General->value,
            'sort_order' => 1,
        ]);

        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $generalGroup->id,
            'name' => 'Test Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $generalGroup->id . '/account-template-checklists/' . $checklist->id . '/supplier', [
                'supplierId' => $this->supplier->id,
            ]);

        $response->assertStatus(500);
    }

    public function test_staff_can_bulk_update_checklist_sort_order(): void
    {
        $checklist1 = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'Checklist 1',
            'sort_order' => 1,
        ]);

        $checklist2 = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'Checklist 2',
            'sort_order' => 2,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists/sort', [
                ['id' => $checklist1->id, 'sortOrder' => 2],
                ['id' => $checklist2->id, 'sortOrder' => 1],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('account_template_checklists', [
            'id' => $checklist1->id,
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('account_template_checklists', [
            'id' => $checklist2->id,
            'sort_order' => 1,
        ]);
    }

    public function test_staff_can_delete_checklist(): void
    {
        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'Test Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists/' . $checklist->id);

        $response->assertStatus(200);

        $this->assertSoftDeleted('account_template_checklists', [
            'id' => $checklist->id,
        ]);
    }

    public function test_create_checklist_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_frequency_validates_required_fields(): void
    {
        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $this->group->id,
            'name' => 'Test Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists/' . $checklist->id . '/frequency', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequencyType', 'frequencyAnchor', 'frequencyValue']);
    }

    public function test_unauthenticated_staff_cannot_access_checklists(): void
    {
        $response = $this->postJson('/api/staff/account-template-checklist-groups/' . $this->group->id . '/account-template-checklists', [
            'name' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    public function test_staff_cannot_create_checklist_in_group_from_other_account(): void
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
            'name' => 'Other Group',
            'event_type' => 'Wedding',
            'checklist_type' => ChecklistGroupTypeEnum::General->value,
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/staff/account-template-checklist-groups/' . $otherGroup->id . '/account-template-checklists', [
                'name' => 'Hacked Checklist',
            ]);

        $response->assertStatus(404);
    }

    public function test_staff_cannot_update_checklist_from_other_account(): void
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
            'name' => 'Other Group',
            'event_type' => 'Wedding',
            'checklist_type' => ChecklistGroupTypeEnum::General->value,
            'sort_order' => 1,
        ]);

        $otherChecklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $otherGroup->id,
            'name' => 'Other Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $otherGroup->id . '/account-template-checklists/' . $otherChecklist->id . '/name', [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(404);
    }

    public function test_staff_cannot_delete_checklist_from_other_account(): void
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
            'name' => 'Other Group',
            'event_type' => 'Wedding',
            'checklist_type' => ChecklistGroupTypeEnum::General->value,
            'sort_order' => 1,
        ]);

        $otherChecklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $otherGroup->id,
            'name' => 'Other Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/staff/account-template-checklist-groups/' . $otherGroup->id . '/account-template-checklists/' . $otherChecklist->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('account_template_checklists', [
            'id' => $otherChecklist->id,
            'deleted_at' => null,
        ]);
    }

    public function test_staff_cannot_assign_supplier_from_other_account_to_checklist(): void
    {
        // Create a Supplier type group for this test
        $supplierGroup = AccountTemplateChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Supplier Group',
            'event_type' => 'Wedding',
            'checklist_type' => ChecklistGroupTypeEnum::Supplier->value,
            'sort_order' => 2,
        ]);

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

        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+9876543210',
            'country_id' => $country->id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '789 Other St',
            'city' => 'Other City',
            'state' => 'Other State',
            'zip' => '54321',
            'country_id' => $country->id,
        ]);

        $otherSupplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $otherAccount->id,
            'company_name' => 'Other Supplier',
            'contact_person' => 'Other Person',
            'contact_number_id' => $contactNumber->id,
            'address_id' => $address->id,
        ]);

        $checklist = AccountTemplateChecklist::create([
            'id' => Str::uuid()->toString(),
            'account_template_checklist_group_id' => $supplierGroup->id,
            'name' => 'Test Checklist',
            'sort_order' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/staff/account-template-checklist-groups/' . $supplierGroup->id . '/account-template-checklists/' . $checklist->id . '/supplier', [
                'supplierId' => $otherSupplier->id,
            ]);

        $response->assertStatus(422);
    }
}
