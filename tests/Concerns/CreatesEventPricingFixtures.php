<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use App\Enums\EventTypeEnum;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Address;
use App\Models\Admin;
use App\Models\Client;
use App\Models\ContactNumber;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\EventPrice;
use App\Models\Staff;
use App\Models\Supplier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait CreatesEventPricingFixtures
{
    protected Country $country;
    protected Account $account;
    protected Staff $staff;
    protected string $token;
    protected Account $otherAccount;
    protected Admin $admin;
    protected Client $client;

    protected function setUpEventPricingFixtures(): void
    {
        $this->country = $this->createTestCountry();
        $this->account = $this->createAccount('Test Account');
        $this->otherAccount = $this->createAccount('Other Account');
        $this->staff = $this->createStaff($this->account, 'staff@test.com');
        $this->token = $this->staff->createToken('staff-token')->plainTextToken;

        $this->admin = Admin::create([
            'id' => Str::uuid()->toString(),
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Admin',
        ]);

        $this->client = Client::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $this->account->id,
            'email' => 'client@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Client',
        ]);
    }

    protected function createAccount(string $name): Account
    {
        return Account::create([
            'id' => Str::uuid()->toString(),
            'name' => $name,
            'status' => AccountStatusEnum::Active,
            'country_id' => $this->country->id,
            'subscription_tier' => AccountSubscriptionTierEnum::Free,
            'timezone' => 'UTC',
        ]);
    }

    protected function createStaff(Account $account, string $email): Staff
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
        ]);
    }

    protected function createPackage(
        EventTypeEnum $eventType,
        string $name = 'Gold Package',
        float $price = 50000.00,
        ?Account $account = null,
    ): EventPackage {
        return EventPackage::factory()->create([
            'account_id' => ($account ?? $this->account)->id,
            'event_type' => $eventType,
            'name' => $name,
            'price' => $price,
            'description' => $name . ' description',
        ]);
    }

    protected function createEvent(?EventPackage $eventPackage = null, ?Account $account = null): Event
    {
        $account ??= $this->account;
        $eventPackage ??= $this->createPackage(EventTypeEnum::Birthday, account: $account);

        return Event::factory()->pending()->create([
            'account_id' => $account->id,
            'event_type' => $eventPackage->event_type,
            'event_package_id' => $eventPackage->id,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createPrice(Event $event, array $attributes = []): EventPrice
    {
        return EventPrice::factory()->create(array_merge([
            'event_id' => $event->id,
            'account_id' => $event->account_id,
        ], $attributes));
    }

    protected function createSupplier(?Account $account = null, string $companyName = 'Acme Catering'): Supplier
    {
        $account ??= $this->account;

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
}
