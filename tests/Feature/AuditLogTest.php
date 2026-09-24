<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Enums\EventTypeEnum;
use App\Enums\ResponsibilityTypeEnum;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\AccountTemplateChecklist;
use App\Models\AccountTemplateChecklistGroup;
use App\Models\Client;
use App\Models\Country;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\EventChecklistGroupLog;
use App\Models\EventChecklistLog;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private Client $client;
    private Account $account;
    private Country $country;
    private string $staffToken;
    private string $clientToken;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->client = Client::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'email' => 'client@account.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Client',
        ]);

        $this->staffToken = $this->staff->createToken('staff-token')->plainTextToken;
        $this->clientToken = $this->client->createToken('client-token')->plainTextToken;
    }

    // Supplier Audit Log Tests

    public function test_supplier_create_generates_audit_log_for_staff(): void
    {
        $this->actingAs($this->staff, 'staff');

        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Supplier',
            'description' => 'A test supplier',
        ]);

        $this->assertDatabaseHas('supplier_logs', [
            'supplier_id' => $supplier->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Create->value,
        ]);

        $log = SupplierLog::where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($log->audit_date);
    }

    public function test_supplier_update_generates_audit_log_for_staff(): void
    {
        $this->actingAs($this->staff, 'staff');

        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Supplier',
        ]);

        // Clear the create log to isolate update test
        SupplierLog::where('supplier_id', $supplier->id)->delete();

        $supplier->update(['name' => 'Updated Supplier']);

        $this->assertDatabaseHas('supplier_logs', [
            'supplier_id' => $supplier->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_supplier_delete_generates_audit_log_for_staff(): void
    {
        $this->actingAs($this->staff, 'staff');

        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Supplier',
        ]);

        // Clear the create log
        SupplierLog::where('supplier_id', $supplier->id)->delete();

        $supplier->delete();

        $this->assertDatabaseHas('supplier_logs', [
            'supplier_id' => $supplier->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Delete->value,
        ]);
    }

    // EventChecklistGroup Audit Log Tests

    public function test_event_checklist_group_create_generates_audit_log_for_staff(): void
    {
        $this->actingAs($this->staff, 'staff');

        $event = \App\Models\Event::factory()->create(['account_id' => $this->account->id]);

        $group = EventChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('event_checklist_group_logs', [
            'event_checklist_group_id' => $group->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Create->value,
        ]);
    }

    public function test_event_checklist_group_update_generates_audit_log_for_client(): void
    {
        $this->actingAs($this->client, 'client');

        $event = \App\Models\Event::factory()->create(['account_id' => $this->account->id]);

        $group = EventChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        // Clear the create log
        EventChecklistGroupLog::where('event_checklist_group_id', $group->id)->delete();

        $group->update(['name' => 'Updated Group']);

        $this->assertDatabaseHas('event_checklist_group_logs', [
            'event_checklist_group_id' => $group->id,
            'audit_by' => $this->client->id,
            'audit_type' => AuditTypeEnum::Client->value,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_event_checklist_group_delete_generates_audit_log(): void
    {
        $this->actingAs($this->staff, 'staff');

        $event = \App\Models\Event::factory()->create(['account_id' => $this->account->id]);

        $group = EventChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        EventChecklistGroupLog::where('event_checklist_group_id', $group->id)->delete();

        $group->delete();

        $this->assertDatabaseHas('event_checklist_group_logs', [
            'event_checklist_group_id' => $group->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Delete->value,
        ]);
    }

    // EventChecklist Audit Log Tests

    public function test_event_checklist_create_generates_audit_log_for_staff(): void
    {
        $this->actingAs($this->staff, 'staff');

        $event = \App\Models\Event::factory()->create(['account_id' => $this->account->id]);
        $group = EventChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $checklist = EventChecklist::create([
            'id' => Str::uuid()->toString(),
            'event_checklist_group_id' => $group->id,
            'name' => 'Test Checklist',
            'description' => 'Test description',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $checklist->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Create->value,
        ]);
    }

    public function test_event_checklist_update_generates_audit_log_for_client(): void
    {
        $this->actingAs($this->client, 'client');

        $event = \App\Models\Event::factory()->create(['account_id' => $this->account->id]);
        $group = EventChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $checklist = EventChecklist::create([
            'id' => Str::uuid()->toString(),
            'event_checklist_group_id' => $group->id,
            'name' => 'Test Checklist',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        EventChecklistLog::where('event_checklist_id', $checklist->id)->delete();

        $checklist->update(['name' => 'Updated Checklist']);

        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $checklist->id,
            'audit_by' => $this->client->id,
            'audit_type' => AuditTypeEnum::Client->value,
            'action' => AuditActionEnum::Update->value,
        ]);
    }

    public function test_event_checklist_delete_generates_audit_log(): void
    {
        $this->actingAs($this->staff, 'staff');

        $event = \App\Models\Event::factory()->create(['account_id' => $this->account->id]);
        $group = EventChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'name' => 'Test Group',
            'event_type' => EventTypeEnum::Birthday,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        $checklist = EventChecklist::create([
            'id' => Str::uuid()->toString(),
            'event_checklist_group_id' => $group->id,
            'name' => 'Test Checklist',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);

        EventChecklistLog::where('event_checklist_id', $checklist->id)->delete();

        $checklist->delete();

        $this->assertDatabaseHas('event_checklist_logs', [
            'event_checklist_id' => $checklist->id,
            'audit_by' => $this->staff->id,
            'audit_type' => AuditTypeEnum::Staff->value,
            'action' => AuditActionEnum::Delete->value,
        ]);
    }

    public function test_no_audit_log_created_when_no_authenticated_user(): void
    {
        // No authentication
        $supplier = Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'name' => 'Test Supplier',
        ]);

        $this->assertDatabaseMissing('supplier_logs', [
            'supplier_id' => $supplier->id,
        ]);
    }
}
