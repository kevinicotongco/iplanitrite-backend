<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Dto\Request\CreateAccountRequestDto;
use App\Dto\Request\GetAccountsRequestDto;
use App\Dto\Request\UpdateAccountRequestDto;
use App\Enums\AccountStatusEnum;
use App\Models\Address;
use App\Models\ContactNumber;
use App\Models\Account;
use App\Models\AccountRole;
use App\Models\Staff;
use App\Notifications\StaffWelcomeNotification;
use App\Services\DocumentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class AccountService
{
    public function __construct(
        private DocumentService $documentService,
    ) {}

    /**
     * @return Collection<int, Account>
     */
    public function getAccounts(GetAccountsRequestDto $request): Collection
    {
        $query = Account::query()
            ->with(['logoDocument', 'address.country', 'country', 'contactNumber.country']);

        if ($request->searchText) {
            $query->where('name', 'ILIKE', '%' . $request->searchText . '%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->tier) {
            $query->where('subscription_tier', $request->tier);
        }

        if ($request->countryId) {
            $query->where('country_id', $request->countryId);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    public function createAccount(CreateAccountRequestDto $request): Account
    {
        $logoDocumentId = null;
        if ($request->logo) {
            $document = $this->documentService->uploadFile($request->logo);
            $logoDocumentId = $document->id;
        }

        $addressId = null;
        if ($request->address) {
            $address = Address::create([
                'line1' => $request->address->line1,
                'line2' => $request->address->line2,
                'city' => $request->address->city,
                'state' => $request->address->state,
                'zip' => $request->address->zip,
                'lat' => $request->address->lat,
                'long' => $request->address->long,
                'country_id' => $request->countryId,
            ]);
            $addressId = $address->id;
        }

        $contactNumberId = null;
        if ($request->contactNumber) {
            $contactNumber = ContactNumber::create([
                'number' => $request->contactNumber,
                'country_id' => $request->countryId,
            ]);
            $contactNumberId = $contactNumber->id;
        }

        $account = Account::create([
            'name' => $request->name,
            'status' => AccountStatusEnum::Active,
            'logo' => $logoDocumentId,
            'description' => $request->description,
            'address_id' => $addressId,
            'country_id' => $request->countryId,
            'contact_number_id' => $contactNumberId,
            'subscription_tier' => $request->subscriptionTier,
            'timezone' => $request->timezone,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $administratorRole = AccountRole::create([
            'account_id' => $account->id,
            'name' => 'Administrator',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $temporaryPassword = Str::random(15);

        $staff = Staff::create([
            'account_id' => $account->id,
            'account_role_id' => $administratorRole->id,
            'email' => $request->staff->email,
            'password' => Hash::make($temporaryPassword),
            'first_name' => $request->staff->firstName,
            'middle_name' => $request->staff->middleName,
            'last_name' => $request->staff->lastName,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $staff->notify(new StaffWelcomeNotification($temporaryPassword, $account->name));

        return $account->load(['logoDocument', 'address.country', 'country', 'contactNumber.country']);
    }

    public function updateAccount(string $accountId, UpdateAccountRequestDto $request): Account
    {
        $account = Account::findOrFail($accountId);

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'subscription_tier' => $request->subscriptionTier,
            'country_id' => $request->countryId,
            'timezone' => $request->timezone,
            'updated_by' => null,
        ];

        if ($request->logo) {
            if ($account->logo) {
                $this->documentService->deleteFile($account->logo);
            }
            $document = $this->documentService->uploadFile($request->logo);
            $updateData['logo'] = $document->id;
        }

        if ($request->address) {
            if ($account->address_id) {
                $account->address->update([
                    'line1' => $request->address->line1,
                    'line2' => $request->address->line2,
                    'city' => $request->address->city,
                    'state' => $request->address->state,
                    'zip' => $request->address->zip,
                    'lat' => $request->address->lat,
                    'long' => $request->address->long,
                    'country_id' => $request->countryId,
                ]);
            } else {
                $address = Address::create([
                    'line1' => $request->address->line1,
                    'line2' => $request->address->line2,
                    'city' => $request->address->city,
                    'state' => $request->address->state,
                    'zip' => $request->address->zip,
                    'lat' => $request->address->lat,
                    'long' => $request->address->long,
                    'country_id' => $request->countryId,
                ]);
                $updateData['address_id'] = $address->id;
            }
        }

        if ($request->contactNumber) {
            if ($account->contact_number_id) {
                $account->contactNumber->update([
                    'number' => $request->contactNumber,
                    'country_id' => $request->countryId,
                ]);
            } else {
                $contactNumber = ContactNumber::create([
                    'number' => $request->contactNumber,
                    'country_id' => $request->countryId,
                ]);
                $updateData['contact_number_id'] = $contactNumber->id;
            }
        }

        $account->update($updateData);

        return $account->fresh(['logoDocument', 'address.country', 'country', 'contactNumber.country']);
    }
}
