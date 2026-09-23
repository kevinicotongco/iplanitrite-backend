<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\LoginResult;
use App\Models\Staff;
use Exception;
use Illuminate\Support\Facades\Hash;

readonly class StaffUserService
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Authenticate staff user and generate JWT token
     *
     * @param string $email
     * @param string $password
     * @return LoginResult|null
     */
    public function login(string $email, string $password): ?LoginResult
    {
        $user = Staff::where('email', $email)->first();

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
     * Retrieve staff profile by ID
     *
     * @param string $staffId
     * @return Staff|null
     */
    public function getProfile(string $staffId): ?Staff
    {
        return Staff::findOrFail($staffId);
    }

    /**
     * Update staff profile information
     *
     * @param string $staffId
     * @param array $data Should contain avatar, firstName, middleName, lastName (camelCase from DTO)
     * @return Staff
     */
    public function updateProfile(string $staffId, array $data): Staff
    {
        $staff = Staff::findOrFail($staffId);

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
     * Change staff password
     *
     * @param string $staffId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(string $staffId, string $currentPassword, string $newPassword): bool
    {
        $staff = Staff::findOrFail($staffId);

        $staff->password = Hash::make($newPassword);

        return $staff->save();
    }
}
