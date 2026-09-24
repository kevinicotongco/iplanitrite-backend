<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\SupplierData;
use App\Dto\Request\AddressRequestDto;
use App\Enums\AuditActionEnum;
use App\Models\Account;
use App\Models\Supplier;
use App\Models\Staff;
use Illuminate\Support\Collection;

readonly class SupplierService
{
    public function __construct(
        private Supplier $supplierModel,
        private ContactNumberService $contactNumberService,
        private AddressService $addressService,
        private AuditLogService $auditLogService,
    ) {}

    /**
     * Get the authenticated staff user
     */
    private function getAuthenticatedUser(): Staff
    {
        $user = auth()->user();
        if (!$user instanceof Staff) {
            throw new \Exception('Authenticated user is not a Staff member');
        }
        return $user;
    }

    /**
     * Get all suppliers for the authenticated user's account
     *
     * @return Collection<int, SupplierData>
     */
    public function getAllSuppliers(): Collection
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $suppliers = $this->supplierModel::where('account_id', $authenticatedUser->account_id)
            ->get();

        return $suppliers->map(fn(Supplier $supplier) => SupplierData::fromModel($supplier));
    }

    /**
     * Get a single supplier by ID
     *
     * @param string $supplierId
     * @return SupplierData
     */
    public function getSupplierById(string $supplierId): SupplierData
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $supplier = $this->supplierModel::where('id', $supplierId)
            ->where('account_id', $authenticatedUser->account_id)
            ->firstOrFail();

        return SupplierData::fromModel($supplier);
    }

    /**
     * Create a new supplier
     *
     * @param string $companyName
     * @param string $contactPerson
     * @param string $contactNumber
     * @param AddressRequestDto $addressDto
     * @return SupplierData
     */
    public function createSupplier(
        string $companyName,
        string $contactPerson,
        string $contactNumber,
        AddressRequestDto $addressDto
    ): SupplierData {
        $authenticatedUser = $this->getAuthenticatedUser();

        // Get account's country ID
        $account = Account::findOrFail($authenticatedUser->account_id);
        $countryId = $account->country_id;

        // Create contact number
        $contactNumberData = $this->contactNumberService->createContactNumber($contactNumber, $countryId);

        // Create address
        $addressData = $this->addressService->createAddress($addressDto, $countryId);

        // Create supplier
        $supplier = $this->supplierModel::create([
            'account_id' => $authenticatedUser->account_id,
            'company_name' => $companyName,
            'contact_person' => $contactPerson,
            'contact_number_id' => $contactNumberData->id,
            'address_id' => $addressData->id,
        ]);

        // Log the create action
        $this->auditLogService->logSupplierAction($supplier, AuditActionEnum::Create);

        return SupplierData::fromModel($supplier);
    }

    /**
     * Update a supplier
     *
     * @param string $supplierId
     * @param string $companyName
     * @param string $contactPerson
     * @param string $contactNumber
     * @param AddressRequestDto $addressDto
     * @return void
     */
    public function updateSupplier(
        string $supplierId,
        string $companyName,
        string $contactPerson,
        string $contactNumber,
        AddressRequestDto $addressDto
    ): void {
        $authenticatedUser = $this->getAuthenticatedUser();

        $supplier = $this->supplierModel::where('id', $supplierId)
            ->where('account_id', $authenticatedUser->account_id)
            ->firstOrFail();

        // Get account's country ID
        $account = Account::findOrFail($authenticatedUser->account_id);
        $countryId = $account->country_id;

        // Update contact number
        $this->contactNumberService->updateContactNumber(
            $supplier->contactNumber,
            $contactNumber,
            $countryId
        );

        // Update address
        $this->addressService->updateAddress(
            $supplier->address,
            $addressDto,
            $countryId
        );

        // Update supplier
        $supplier->update([
            'company_name' => $companyName,
            'contact_person' => $contactPerson,
        ]);

        // Log the update action
        $this->auditLogService->logSupplierAction($supplier, AuditActionEnum::Update);
    }

    /**
     * Delete a supplier (soft delete)
     *
     * @param string $supplierId
     * @return void
     */
    public function deleteSupplier(string $supplierId): void
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $supplier = $this->supplierModel::where('id', $supplierId)
            ->where('account_id', $authenticatedUser->account_id)
            ->firstOrFail();

        $supplier->delete();

        // Log the delete action
        $this->auditLogService->logSupplierAction($supplier, AuditActionEnum::Delete);
    }
}
