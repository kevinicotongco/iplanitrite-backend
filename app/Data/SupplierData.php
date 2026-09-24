<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Supplier;

final readonly class SupplierData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $companyName,
        public string $contactPerson,
        public string $contactNumberId,
        public string $addressId,
    ) {}

    public static function fromModel(Supplier $supplier): self
    {
        return new self(
            id: $supplier->id,
            accountId: $supplier->account_id,
            companyName: $supplier->company_name,
            contactPerson: $supplier->contact_person,
            contactNumberId: $supplier->contact_number_id,
            addressId: $supplier->address_id,
        );
    }
}
