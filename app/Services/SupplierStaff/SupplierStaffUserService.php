<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Data\LoginResult;
use App\Models\SupplierStaff;
use Exception;
use Illuminate\Support\Facades\Hash;

readonly class SupplierStaffUserService
{
    /**
     * Constructor
     */
    public function __construct(
        private SupplierStaff $model
    ) {
    }

    /**
     * Authenticate supplier staff user and generate JWT token
     *
     * @param string $email
     * @param string $password
     * @return LoginResult|null
     */
    public function login(string $email, string $password): ?LoginResult
    {
        $user = $this->model::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken('staff-token')->plainTextToken;

        return new LoginResult(
            token: $token,
            user: $user
        );
    }

    /**
     * Retrieve supplier staff profile by ID
     *
     * @param string $staffId
     * @return SupplierStaff|null
     */
    public function getProfile(string $staffId): ?SupplierStaff
    {
        return $this->model::findOrFail($staffId);
    }

    /**
     * Update supplier staff profile information
     *
     * @param string $staffId
     * @param array $data Should contain avatar, firstName, middleName, lastName (camelCase from DTO)
     * @return SupplierStaff
     */
    public function updateProfile(string $staffId, array $data): SupplierStaff
    {
        $staff = $this->model::findOrFail($staffId);

        $updateData = [];

        if (isset($data['avatar'])) {
            $updateData['profile_picture'] = $data['avatar'];
        }

        if (isset($data['firstName'])) {
            $updateData['first_name'] = $data['firstName'];
        }

        if (isset($data['middleName'])) {
            $updateData['middle_name'] = $data['middleName'];
        }

        if (isset($data['lastName'])) {
            $updateData['last_name'] = $data['lastName'];
        }

        $staff->update($updateData);

        return $staff->fresh();
    }

    /**
     * Change supplier staff password
     *
     * @param string $staffId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(string $staffId, string $currentPassword, string $newPassword): bool
    {
        $staff = $this->model::findOrFail($staffId);

        $staff->password = Hash::make($newPassword);

        return $staff->save();
    }
}
