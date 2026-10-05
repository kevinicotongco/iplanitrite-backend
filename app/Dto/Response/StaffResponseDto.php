<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\StaffData;
use App\Models\Staff;

readonly class StaffResponseDto
{
    public function __construct(
        public string                    $id,
        public string                    $accountId,
        public string                    $accountRoleId,
        public string                    $email,
        public string                    $firstName,
        public ?string                   $middleName,
        public string                    $lastName,
        public ?string                   $profilePicture,
        public ?AddressResponseDto       $address,
        public ?ContactNumberResponseDto $contactNumber,
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
            profilePicture: $staff->profile_picture,
            address: $staff->address
                ? AddressResponseDto::fromModel($staff->address)
                : null,
            contactNumber: $staff->contactNumber
                ? ContactNumberResponseDto::fromModel($staff->contactNumber)
                : null,
        );
    }

    public static function fromData(StaffData $data): self
    {
        return new self(
            id: $data->id,
            accountId: $data->accountId,
            accountRoleId: $data->accountRoleId,
            email: $data->email,
            firstName: $data->firstName,
            middleName: $data->middleName,
            lastName: $data->lastName,
            profilePicture: $data->profilePicture,
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
            'accountId' => $this->accountId,
            'accountRoleId' => $this->accountRoleId,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'profilePicture' => $this->profilePicture,
            'address' => $this->address?->toArray(),
            'contactNumber' => $this->contactNumber?->toArray(),
        ];
    }
}
