<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Data\SupplierData;
use App\Dto\Request\AddressRequestDto;
use App\Enums\AuditActionEnum;
use App\Models\Account;
use App\Models\Supplier;
use App\Services\AddressService;
use App\Services\ContactNumberService;
use Illuminate\Support\Collection;

readonly class SupplierService
{
    public function __construct(
        private readonly StaffAuthenticatedUser $authenticatedUser,
        private Supplier $supplierModel,
        private ContactNumberService $contactNumberService,
        private AddressService $addressService,
        private AuditLogService $auditLogService,
    ) {}

    public function getAllSuppliers(): Collection
    {
        $suppliers = $this->supplierModel::where('account_id', $this->authenticatedUser->accountId)
            ->get();

        return $suppliers->map(fn(Supplier $supplier) => SupplierData::fromModel($supplier));
    }

    public function getSupplierById(string $supplierId): SupplierData
    {
        $supplier = $this->supplierModel::where('id', $supplierId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();

        return SupplierData::fromModel($supplier);
    }

    public function createSupplier(
        string $companyName,
        string $contactPerson,
        string $contactNumber,
        AddressRequestDto $addressDto
    ): SupplierData {
        $account = Account::findOrFail($this->authenticatedUser->accountId);
        $countryId = $account->country_id;

        $contactNumberData = $this->contactNumberService->createContactNumber($contactNumber, $countryId);
        $addressData = $this->addressService->createAddress($addressDto, $countryId);

        $supplier = $this->supplierModel::create([
            'account_id' => $this->authenticatedUser->accountId,
            'company_name' => $companyName,
            'contact_person' => $contactPerson,
            'contact_number_id' => $contactNumberData->id,
            'address_id' => $addressData->id,
        ]);

        $this->auditLogService->logSupplierAction($supplier, AuditActionEnum::Create);

        return SupplierData::fromModel($supplier);
    }

    public function updateSupplier(
        string $supplierId,
        string $companyName,
        string $contactPerson,
        string $contactNumber,
        AddressRequestDto $addressDto
    ): void {
        $supplier = $this->supplierModel::where('id', $supplierId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();

        $account = Account::findOrFail($this->authenticatedUser->accountId);
        $countryId = $account->country_id;

        $this->contactNumberService->updateContactNumber($supplier->contactNumber, $contactNumber, $countryId);
        $this->addressService->updateAddress($supplier->address, $addressDto, $countryId);

        $supplier->update([
            'company_name' => $companyName,
            'contact_person' => $contactPerson,
        ]);

        $this->auditLogService->logSupplierAction($supplier, AuditActionEnum::Update);
    }

    public function deleteSupplier(string $supplierId): void
    {
        $supplier = $this->supplierModel::where('id', $supplierId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();

        $supplier->delete();

        $this->auditLogService->logSupplierAction($supplier, AuditActionEnum::Delete);
    }
}
