<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\StaffData;
use App\Dto\Request\ManageStaffRequestDto;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class StaffManagementService
{
    public function __construct(
        private Staff $authenticatedUser,
        private AddressService $addressService,
        private ContactNumberService $contactNumberService,
    ) {}

    /**
     * @param string $accountId
     * @return Collection<StaffData>
     */
    public function getStaff(string $accountId): Collection
    {
        $staff = Staff::where('account_id', $accountId)
            ->with(['address', 'contactNumber', 'profilePictureDocument'])
            ->get();

        return $staff->map(fn($s) => StaffData::fromModel($s));
    }

    /**
     * @param string $accountId
     * @param string $countryId
     * @param ManageStaffRequestDto $dto
     * @return void
     */
    public function createStaff(string $accountId, string $countryId, ManageStaffRequestDto $dto): void
    {
        $addressId = null;
        $contactNumberId = null;

        if ($dto->address) {
            $address = $this->addressService->createAddress($dto->address, $countryId);
            $addressId = $address->id;
        }

        if ($dto->contactNumber) {
            $contactNumber = $this->contactNumberService->createContactNumber($dto->contactNumber, $countryId);
            $contactNumberId = $contactNumber->id;
        }

        // Generate a random default password
        $defaultPassword = Str::random(16);

        $staff = Staff::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $accountId,
            'account_role_id' => $dto->role,
            'email' => $dto->email,
            'password' => Hash::make($defaultPassword),
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'address_id' => $addressId,
            'contact_number_id' => $contactNumberId,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        // TODO: Send email with login credentials to the new staff member
    }

    /**
     * @param string $staffId
     * @param string $accountId
     * @param string $countryId
     * @param ManageStaffRequestDto $dto
     * @return void
     */
    public function updateStaff(string $staffId, string $accountId, string $countryId, ManageStaffRequestDto $dto): void
    {
        $staff = Staff::where('id', $staffId)
            ->where('account_id', $accountId)
            ->firstOrFail();

        $addressId = $staff->address_id;
        $contactNumberId = $staff->contact_number_id;

        if ($dto->address) {
            if ($addressId) {
                $this->addressService->updateAddress($staff->address, $dto->address, $countryId);
            } else {
                $address = $this->addressService->createAddress($dto->address, $countryId);
                $addressId = $address->id;
            }
        }

        if ($dto->contactNumber) {
            if ($contactNumberId) {
                $this->contactNumberService->updateContactNumber($staff->contactNumber, $dto->contactNumber, $countryId);
            } else {
                $contactNumber = $this->contactNumberService->createContactNumber($dto->contactNumber, $countryId);
                $contactNumberId = $contactNumber->id;
            }
        }

        $staff->update([
            'account_role_id' => $dto->role,
            'email' => $dto->email,
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'address_id' => $addressId,
            'contact_number_id' => $contactNumberId,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    /**
     * @param string $staffId
     * @param string $accountId
     * @return void
     */
    public function deleteStaff(string $staffId, string $accountId): void
    {
        $staff = Staff::where('id', $staffId)
            ->where('account_id', $accountId)
            ->firstOrFail();

        $staff->delete();
    }
}