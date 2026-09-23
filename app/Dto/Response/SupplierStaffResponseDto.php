<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\SupplierStaffData;
use App\Models\SupplierStaff;

readonly class SupplierStaffResponseDto
{
    public function __construct(
        public string $id,
        public string $supplierId,
        public string $supplierRoleId,
        public string $email,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?DocumentResponseDto $profilePicture,
        public ?AddressResponseDto $address,
        public ?ContactNumberResponseDto $contactNumber,
    ) {}

    public static function fromModel(SupplierStaff $supplierStaff): self
    {
        return new self(
            id: $supplierStaff->id,
            supplierId: $supplierStaff->supplier_id,
            supplierRoleId: $supplierStaff->supplier_role_id,
            email: $supplierStaff->email,
            firstName: $supplierStaff->first_name,
            middleName: $supplierStaff->middle_name,
            lastName: $supplierStaff->last_name,
            profilePicture: $supplierStaff->profilePictureDocument
                ? DocumentResponseDto::fromModel($supplierStaff->profilePictureDocument)
                : null,
            address: $supplierStaff->address
                ? AddressResponseDto::fromModel($supplierStaff->address)
                : null,
            contactNumber: $supplierStaff->contactNumber
                ? ContactNumberResponseDto::fromModel($supplierStaff->contactNumber)
                : null,
        );
    }

    public static function fromData(SupplierStaffData $data): self
    {
        return new self(
            id: $data->id,
            supplierId: $data->supplierId,
            supplierRoleId: $data->supplierRoleId,
            email: $data->email,
            firstName: $data->firstName,
            middleName: $data->middleName,
            lastName: $data->lastName,
            profilePicture: $data->profilePictureDocument
                ? DocumentResponseDto::fromModel($data->profilePictureDocument)
                : null,
            address: $data->address
                ? AddressResponseDto::fromModel($data->address)
                : null,
            contactNumber: $data->contactNumber
                ? ContactNumberResponseDto::fromModel($data->contactNumber)
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'supplierId' => $this->supplierId,
            'supplierRoleId' => $this->supplierRoleId,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'profilePicture' => $this->profilePicture?->toArray(),
            'address' => $this->address?->toArray(),
            'contactNumber' => $this->contactNumber?->toArray(),
        ];
    }
}
