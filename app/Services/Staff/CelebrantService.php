<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Data\CelebrantData;
use App\Dto\Request\CelebrantRequestDto;
use App\Models\Celebrant;
use App\Services\AddressService;
use App\Services\ContactNumberService;

readonly class CelebrantService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private Celebrant $celebrantModel,
        private AddressService $addressService,
        private ContactNumberService $contactNumberService,
    ) {}

    /**
     * Create a celebrant with optional address and contact number
     *
     * @param CelebrantRequestDto $dto
     * @param string $countryId
     * @return CelebrantData
     */
    public function createCelebrant(CelebrantRequestDto $dto, string $countryId): CelebrantData
    {
        $addressId = null;
        if ($dto->address) {
            $address = $this->addressService->createAddress($dto->address, $countryId);
            $addressId = $address->id;
        }

        $contactNumberId = null;
        if ($dto->contactNumber) {
            $contactNumber = $this->contactNumberService->createContactNumber($dto->contactNumber, $countryId);
            $contactNumberId = $contactNumber->id;
        }

        $celebrant = $this->celebrantModel::create([
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'address_id' => $addressId,
            'contact_number_id' => $contactNumberId,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return CelebrantData::fromModel($celebrant);
    }

    /**
     * Update a celebrant with optional address and contact number
     *
     * @param Celebrant $celebrant
     * @param CelebrantRequestDto $dto
     * @param string $countryId
     * @return void
     */
    public function updateCelebrant(Celebrant $celebrant, CelebrantRequestDto $dto, string $countryId): void
    {
        $addressId = null;
        if ($dto->address) {
            if ($celebrant->address_id) {
                $this->addressService->updateAddress($celebrant->address, $dto->address, $countryId);
                $addressId = $celebrant->address_id;
            } else {
                $address = $this->addressService->createAddress($dto->address, $countryId);
                $addressId = $address->id;
            }
        }

        $contactNumberId = null;
        if ($dto->contactNumber) {
            if ($celebrant->contact_number_id) {
                $this->contactNumberService->updateContactNumber($celebrant->contactNumber, $dto->contactNumber, $countryId);
                $contactNumberId = $celebrant->contact_number_id;
            } else {
                $contactNumber = $this->contactNumberService->createContactNumber($dto->contactNumber, $countryId);
                $contactNumberId = $contactNumber->id;
            }
        }

        $celebrant->update([
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'address_id' => $addressId,
            'contact_number_id' => $contactNumberId,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }
}
