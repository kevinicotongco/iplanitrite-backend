<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\LoginResult;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class ClientUserService
{
    /**
     * Constructor
     */
    public function __construct(
        private Client $model
    ) {
    }

    /**
     * Authenticate client user and generate JWT token
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

        $token = $user->createToken('client-token')->plainTextToken;

        return new LoginResult(
            token: $token,
            user: $user
        );
    }

    /**
     * Retrieve client profile by ID
     *
     * @param string $clientId
     * @return Client|null
     */
    public function getProfile(string $clientId): ?Client
    {
        return $this->model::findOrFail($clientId);
    }

    /**
     * Update client profile information
     *
     * @param string $clientId
     * @param array $data Should contain avatar, firstName, middleName, lastName (camelCase from DTO)
     * @return Client
     */
    public function updateProfile(string $clientId, array $data): Client
    {
        $client = $this->model::findOrFail($clientId);

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

        $client->update($updateData);

        return $client->fresh();
    }

    /**
     * Change client password
     *
     * @param string $clientId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(string $clientId, string $currentPassword, string $newPassword): bool
    {
        $client = $this->model::findOrFail($clientId);

        $client->password = Hash::make($newPassword);

        return $client->save();
    }
}
