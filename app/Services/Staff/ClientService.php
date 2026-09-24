<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\StaffAuthenticatedUser;
use App\Dto\Request\ClientCreateRequestDto;
use App\Models\Client;
use App\Notifications\ClientWelcomeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class ClientService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private Client $clientModel,
    ) {}

    /**
     * Create or get existing clients and return their IDs
     *
     * @param string $accountId
     * @param string $eventName
     * @param array<ClientCreateRequestDto> $clientDtos
     * @return array<string> Array of client IDs
     */
    public function createOrGetClients(string $accountId, string $eventName, array $clientDtos): array
    {
        $clientIds = [];

        foreach ($clientDtos as $clientDto) {
            // Check if client already exists
            $client = $this->clientModel::where('email', $clientDto->email)
                ->where('account_id', $accountId)
                ->first();

            if (!$client) {
                // Generate temporary password
                $temporaryPassword = Str::random(12);

                // Create new client
                $client = $this->clientModel::create([
                    'account_id' => $accountId,
                    'email' => $clientDto->email,
                    'password' => Hash::make($temporaryPassword),
                    'first_name' => $clientDto->firstName,
                    'last_name' => $clientDto->lastName,
                    'created_by' => $this->authenticatedUser->id,
                    'updated_by' => $this->authenticatedUser->id,
                ]);

                // Send welcome notification
                $client->notify(new ClientWelcomeNotification($temporaryPassword, $eventName));
            }

            $clientIds[] = $client->id;
        }

        return $clientIds;
    }
}
