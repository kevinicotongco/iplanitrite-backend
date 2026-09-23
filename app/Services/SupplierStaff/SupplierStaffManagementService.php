<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Dto\Request\ManageSupplierStaffRequestDto;
use App\Models\SupplierStaff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class SupplierStaffManagementService
{
    public function __construct(
        private SupplierStaff $authenticatedUser,
        private AddressService $addressService,
        private ContactNumberService $contactNumberService,
    ) {}

    /**
     * Get all staff for a supplier
     *
     * @param string $supplierId
     * @return Collection<SupplierStaff>
     */
    public function getStaff(string $supplierId): Collection
    {
        return SupplierStaff::where('supplier_id', $supplierId)
            ->with(['address', 'contactNumber', 'profilePictureDocument'])
            ->get();
    }

    /**
     * Create a new supplier staff member
     *
     * @param string $supplierId
     * @param string $countryId
     * @param ManageSupplierStaffRequestDto $dto
     * @return void
     */
    public function createStaff(string $supplierId, string $countryId, ManageSupplierStaffRequestDto $dto): void
    {
        $addressId = null;
        $contactNumberId = null;

        if ($dto->address) {
            $address = $this->addressService->createAddress($dto->address);
            $addressId = $address->id;
        }

        if ($dto->contactNumber) {
            $contactNumber = $this->contactNumberService->createContactNumber($dto->contactNumber, $countryId);
            $contactNumberId = $contactNumber->id;
        }

        // Generate a random default password
        $defaultPassword = Str::random(16);

        SupplierStaff::create([
            'id' => Str::uuid()->toString(),
            'supplier_id' => $supplierId,
            'supplier_role_id' => $dto->role,
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
     * Update an existing supplier staff member
     *
     * @param string $staffId
     * @param string $supplierId
     * @param string $countryId
     * @param ManageSupplierStaffRequestDto $dto
     * @return void
     */
    public function updateStaff(string $staffId, string $supplierId, string $countryId, ManageSupplierStaffRequestDto $dto): void
    {
        $staff = SupplierStaff::where('id', $staffId)
            ->where('supplier_id', $supplierId)
            ->firstOrFail();

        $addressId = $staff->address_id;
        $contactNumberId = $staff->contact_number_id;

        if ($dto->address) {
            if ($addressId) {
                $this->addressService->updateAddress($staff->address, $dto->address);
            } else {
                $address = $this->addressService->createAddress($dto->address);
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
            'supplier_role_id' => $dto->role,
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
     * Delete a supplier staff member
     *
     * @param string $staffId
     * @param string $supplierId
     * @return void
     */
    public function deleteStaff(string $staffId, string $supplierId): void
    {
        $staff = SupplierStaff::where('id', $staffId)
            ->where('supplier_id', $supplierId)
            ->firstOrFail();

        $staff->delete();
    }
}
