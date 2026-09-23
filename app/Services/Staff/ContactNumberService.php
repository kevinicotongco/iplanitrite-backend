<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\ContactNumberData;
use App\Models\ContactNumber;
use App\Models\Staff;

readonly class ContactNumberService
{
    public function __construct(
        private Staff $authenticatedUser,
        private ContactNumber $contactNumberModel,
    ) {}

    /**
     * Create a contact number
     *
     * @param string $number
     * @param string $countryId
     * @return ContactNumberData
     */
    public function createContactNumber(string $number, string $countryId): ContactNumberData
    {
        $contactNumber = $this->contactNumberModel::create([
            'number' => $number,
            'country_id' => $countryId,
        ]);

        return ContactNumberData::fromModel($contactNumber);
    }

    /**
     * Update a contact number
     *
     * @param ContactNumber $contactNumber
     * @param string $number
     * @param string $countryId
     * @return void
     */
    public function updateContactNumber(ContactNumber $contactNumber, string $number, string $countryId): void
    {
        $contactNumber->update([
            'number' => $number,
            'country_id' => $countryId,
        ]);
    }
}
