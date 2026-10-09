<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Dto\Request\CreateSupplierRequestDto;
use App\Enums\AuditActionEnum;
use App\Models\Account;
use App\Models\Event;
use App\Models\Supplier;
use App\Services\AddressService;
use App\Services\ContactNumberService;

readonly class SupplierService
{
    public function __construct(
        private Supplier $supplierModel,
        private Account $accountModel,
        private ContactNumberService $contactNumberService,
        private AddressService $addressService,
        private AuditLogService $auditLogService,
    ) {}

    public function findSupplierForEvent(Event $event, string $supplierId): ?Supplier
    {
        return $this->supplierModel::where('id', $supplierId)
            ->where('account_id', $event->account_id)
            ->with(['contactNumber', 'address.country'])
            ->first();
    }

    public function createSupplier(Event $event, CreateSupplierRequestDto $dto): Supplier
    {
        $countryId = $this->accountModel::findOrFail($event->account_id)->country_id;

        $contactNumberData = $this->contactNumberService->createContactNumber($dto->contactNumber, $countryId);
        $addressData = $this->addressService->createAddress($dto->address, $countryId);

        $supplier = $this->supplierModel::create([
            'account_id' => $event->account_id,
            'company_name' => $dto->companyName,
            'contact_person' => $dto->contactPerson,
            'contact_number_id' => $contactNumberData->id,
            'address_id' => $addressData->id,
        ]);

        $this->auditLogService->logSupplierAction($supplier, AuditActionEnum::Create);

        return $supplier->load(['contactNumber', 'address.country']);
    }
}
