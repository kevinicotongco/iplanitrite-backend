<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Data\LoginResult;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    /**
     * Constructor
     */
    public function __construct(
        private Admin $model
    ) {
    }

    /**
     * Authenticate admin user and generate token
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

        $token = $user->createToken('admin-token')->plainTextToken;

        return new LoginResult(
            token: $token,
            user: $user
        );
    }

    /**
     * Retrieve admin profile by ID
     *
     * @param string $adminId
     * @return Admin|null
     */
    public function getProfile(string $adminId): ?Admin
    {
        return $this->model::findOrFail($adminId);
    }

    /**
     * Update admin profile information
     *
     * @param string $adminId
     * @param array $data Should contain avatar, firstName, lastName
     * @return Admin
     */
    public function updateProfile(string $adminId, array $data): Admin
    {
        $admin = $this->model::findOrFail($adminId);

        $updateData = [];

        if (isset($data['avatar'])) {
            $updateData['avatar'] = $data['avatar'];
        }

        if (isset($data['firstName'])) {
            $updateData['first_name'] = $data['firstName'];
        }

        if (isset($data['lastName'])) {
            $updateData['last_name'] = $data['lastName'];
        }

        $admin->update($updateData);

        return $admin->fresh();
    }

    /**
     * Change admin password
     *
     * @param string $adminId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(string $adminId, string $currentPassword, string $newPassword): bool
    {
        $admin = $this->model::findOrFail($adminId);

        $admin->password = Hash::make($newPassword);

        return $admin->save();
    }
}
