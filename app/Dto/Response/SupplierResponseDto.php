<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\SupplierData;
use App\Models\Supplier;

readonly class SupplierResponseDto
{
    public function __construct(
        public string $id,
        public string $companyName,
        public string $contactPerson,
        public ContactNumberResponseDto $contactNumber,
        public AddressResponseDto $address,
    ) {}

    public static function fromModel(Supplier $supplier): self
    {
        return new self(
            id: $supplier->id,
            companyName: $supplier->company_name,
            contactPerson: $supplier->contact_person,
            contactNumber: ContactNumberResponseDto::fromModel($supplier->contactNumber),
            address: AddressResponseDto::fromModel($supplier->address),
        );
    }

    public static function fromSupplierData(SupplierData $supplierData, Supplier $supplier): self
    {
        return new self(
            id: $supplierData->id,
            companyName: $supplierData->companyName,
            contactPerson: $supplierData->contactPerson,
            contactNumber: ContactNumberResponseDto::fromModel($supplier->contactNumber),
            address: AddressResponseDto::fromModel($supplier->address),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'companyName' => $this->companyName,
            'contactPerson' => $this->contactPerson,
            'contactNumber' => $this->contactNumber->toArray(),
            'address' => $this->address->toArray(),
        ];
    }
}
