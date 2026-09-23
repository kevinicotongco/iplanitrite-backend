<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Address;
use App\Models\ContactNumber;
use App\Models\Document;
use App\Models\SupplierStaff;

final readonly class SupplierStaffData
{
    public function __construct(
        public string $id,
        public string $supplierId,
        public string $supplierRoleId,
        public string $email,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?string $addressId,
        public ?string $contactNumberId,
        public ?string $profilePictureId,
        public ?Address $address,
        public ?ContactNumber $contactNumber,
        public ?Document $profilePictureDocument,
    ) {}

    public static function fromModel(SupplierStaff $staff): self
    {
        return new self(
            id: $staff->id,
            supplierId: $staff->supplier_id,
            supplierRoleId: $staff->supplier_role_id,
            email: $staff->email,
            firstName: $staff->first_name,
            middleName: $staff->middle_name,
            lastName: $staff->last_name,
            addressId: $staff->address_id,
            contactNumberId: $staff->contact_number_id,
            profilePictureId: $staff->profile_picture,
            address: $staff->address,
            contactNumber: $staff->contactNumber,
            profilePictureDocument: $staff->profilePictureDocument,
        );
    }
}
