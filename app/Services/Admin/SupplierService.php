<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Dto\Request\CreateSupplierRequestDto;
use App\Dto\Request\GetSuppliersRequestDto;
use App\Dto\Request\UpdateSupplierRequestDto;
use App\Enums\SupplierStatusEnum;
use App\Models\Address;
use App\Models\ContactNumber;
use App\Models\Supplier;
use App\Models\SupplierRole;
use App\Models\SupplierStaff;
use App\Notifications\SupplierStaffWelcomeNotification;
use App\Services\DocumentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class SupplierService
{
    public function __construct(
        private DocumentService $documentService,
    ) {}

    /**
     * @return Collection<int, Supplier>
     */
    public function getSuppliers(GetSuppliersRequestDto $request): Collection
    {
        $query = Supplier::query()
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

    public function createSupplier(CreateSupplierRequestDto $request, string $adminId): Supplier
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

        $supplier = Supplier::create([
            'name' => $request->name,
            'status' => SupplierStatusEnum::Active,
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

        $administratorRole = SupplierRole::create([
            'supplier_id' => $supplier->id,
            'name' => 'Administrator',
            'created_by' => null,
            'updated_by' => null,
        ]);

        $temporaryPassword = Str::random(15);

        $supplierStaff = SupplierStaff::create([
            'supplier_id' => $supplier->id,
            'supplier_role_id' => $administratorRole->id,
            'email' => $request->supplierStaff->email,
            'password' => Hash::make($temporaryPassword),
            'first_name' => $request->supplierStaff->firstName,
            'middle_name' => $request->supplierStaff->middleName,
            'last_name' => $request->supplierStaff->lastName,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $supplierStaff->notify(new SupplierStaffWelcomeNotification($temporaryPassword, $supplier->name));

        return $supplier->load(['logoDocument', 'address.country', 'country', 'contactNumber.country']);
    }

    public function updateSupplier(string $supplierId, UpdateSupplierRequestDto $request, string $adminId): Supplier
    {
        $supplier = Supplier::findOrFail($supplierId);

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'subscription_tier' => $request->subscriptionTier,
            'country_id' => $request->countryId,
            'timezone' => $request->timezone,
            'updated_by' => null,
        ];

        if ($request->logo) {
            if ($supplier->logo) {
                $this->documentService->deleteFile($supplier->logo);
            }
            $document = $this->documentService->uploadFile($request->logo);
            $updateData['logo'] = $document->id;
        }

        if ($request->address) {
            if ($supplier->address_id) {
                $supplier->address->update([
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
            if ($supplier->contact_number_id) {
                $supplier->contactNumber->update([
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

        $supplier->update($updateData);

        return $supplier->fresh(['logoDocument', 'address.country', 'country', 'contactNumber.country']);
    }
}
