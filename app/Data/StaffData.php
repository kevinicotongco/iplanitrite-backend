<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Address;
use App\Models\ContactNumber;
use App\Models\Staff;

final readonly class StaffData
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $accountRoleId,
        public string $email,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public ?string $addressId,
        public ?string $contactNumberId,
        public ?string $profilePicture,
        public ?Address $address,
        public ?ContactNumber $contactNumber,
    ) {}

    public static function fromModel(Staff $staff): self
    {
        return new self(
            id: $staff->id,
            accountId: $staff->account_id,
            accountRoleId: $staff->account_role_id,
            email: $staff->email,
            firstName: $staff->first_name,
            middleName: $staff->middle_name,
            lastName: $staff->last_name,
            addressId: $staff->address_id,
            contactNumberId: $staff->contact_number_id,
            profilePicture: $staff->profile_picture,
            address: $staff->address,
            contactNumber: $staff->contactNumber,
        );
    }
}
