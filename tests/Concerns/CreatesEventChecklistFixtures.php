<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventChecklistStatusEnum;
use App\Enums\EventTypeEnum;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Address;
use App\Models\Client;
use App\Models\ContactNumber;
use App\Models\Country;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\Staff;
use App\Models\Supplier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait CreatesEventChecklistFixtures
{
    protected Country $country;
    protected Account $account;
    protected Staff $staff;
    protected string $token;
    protected Event $event;
    protected Client $client;
    protected Supplier $supplier;
    protected EventChecklistGroup $supplierGroup;
    protected EventChecklistGroup $generalGroup;
    protected EventChecklist $supplierChecklist;
    protected EventChecklist $generalChecklist;

    protected function setUpEventChecklistFixtures(): void
    {
        $this->country = $this->createTestCountry();
        $this->account = $this->createAccount('Test Account');

        $this->staff = $this->createStaff($this->account, 'staff@test.com', $this->createDocument('staff.png'));
        $this->token = $this->loginStaff('staff@test.com');

        $this->event = $this->createEvent($this->account);

        $this->client = $this->createClient($this->account, 'client@test.com');
        $this->attachClientToEvent($this->event, $this->client);

        $this->supplier = $this->createSupplier($this->account, 'Test Supplier');

        $this->supplierGroup = $this->createGroup($this->event, 'Supplier Group', ChecklistGroupTypeEnum::Supplier, 1);
        $this->generalGroup = $this->createGroup($this->event, 'General Group', ChecklistGroupTypeEnum::General, 1);

        $this->supplierChecklist = $this->createChecklist($this->supplierGroup, 'Supplier Checklist', 1);
        $this->generalChecklist = $this->createChecklist($this->generalGroup, 'General Checklist', 1);
    }

    protected function createAccount(string $name): Account
    {
        return Account::create([
            'id' => Str::uuid()->toString(),
            'name' => $name,
            'status' => 'Active',
            'subscription_tier' => 'Free',
            'description' => 'Test Description',
            'country_id' => $this->country->id,
            'timezone' => 'UTC',
        ]);
    }

    protected function createStaff(Account $account, string $email, ?Document $profilePicture = null): Staff
    {
        $role = AccountRole::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $account->id,
            'name' => 'Manager',
        ]);

        return Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $account->id,
            'account_role_id' => $role->id,
            'email' => $email,
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Staff',
            'profile_picture' => $profilePicture?->id,
        ]);
    }

    protected function createClient(Account $account, string $email): Client
    {
        return Client::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $account->id,
            'email' => $email,
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Client',
        ]);
    }

    protected function createDocument(string $name): Document
    {
        return Document::create([
            'id' => Str::uuid()->toString(),
            'name' => $name,
            'display_name' => $name,
            'url' => 'https://example.com/' . $name,
            'size' => 1024,
            'mime_type' => 'image/png',
        ]);
    }

    protected function createEvent(Account $account): Event
    {
        return Event::factory()->pending()->create([
            'account_id' => $account->id,
            'event_type' => EventTypeEnum::Wedding,
        ]);
    }

    protected function attachClientToEvent(Event $event, Client $client): void
    {
        $event->clients()->attach($client->id, ['id' => Str::uuid()->toString()]);
    }

    protected function createSupplier(Account $account, string $companyName): Supplier
    {
        $contactNumber = ContactNumber::create([
            'id' => Str::uuid()->toString(),
            'number' => '+1234567890',
            'country_id' => $this->country->id,
        ]);

        $address = Address::create([
            'id' => Str::uuid()->toString(),
            'line1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'zip' => '12345',
            'country_id' => $this->country->id,
        ]);

        return Supplier::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $account->id,
            'company_name' => $companyName,
            'contact_person' => 'John Doe',
            'contact_number_id' => $contactNumber->id,
            'address_id' => $address->id,
        ]);
    }

    protected function createGroup(Event $event, string $name, ChecklistGroupTypeEnum $checklistType, int $sortOrder): EventChecklistGroup
    {
        return EventChecklistGroup::create([
            'id' => Str::uuid()->toString(),
            'event_id' => $event->id,
            'name' => $name,
            'event_type' => $event->event_type,
            'checklist_type' => $checklistType,
            'sort_order' => $sortOrder,
        ]);
    }

    protected function createChecklist(EventChecklistGroup $group, string $name, int $sortOrder): EventChecklist
    {
        return EventChecklist::create([
            'id' => Str::uuid()->toString(),
            'event_checklist_group_id' => $group->id,
            'name' => $name,
            'status' => EventChecklistStatusEnum::Pending,
            'sort_order' => $sortOrder,
        ]);
    }

    protected function loginStaff(string $email): string
    {
        return $this->postJson('/api/staff/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }

    protected function loginClient(string $email): string
    {
        return $this->postJson('/api/clients/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('token');
    }

    protected function groupsUrl(?Event $event = null): string
    {
        return '/api/staff/events/' . ($event ?? $this->event)->id . '/checklist-groups';
    }

    protected function checklistsUrl(EventChecklistGroup $group, ?Event $event = null): string
    {
        return $this->groupsUrl($event) . '/' . $group->id . '/checklists';
    }
}
