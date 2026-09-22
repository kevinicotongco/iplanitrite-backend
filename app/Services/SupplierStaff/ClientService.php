<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Dto\Request\ClientCreateRequestDto;
use App\Models\Client;
use App\Models\SupplierStaff;
use App\Notifications\ClientWelcomeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class ClientService
{
    public function __construct(
        private SupplierStaff $authenticatedUser,
        private Client $clientModel,
    ) {}

    /**
     * Create or get existing clients and return their IDs
     *
     * @param string $supplierId
     * @param string $eventName
     * @param array<ClientCreateRequestDto> $clientDtos
     * @return array<string> Array of client IDs
     */
    public function createOrGetClients(string $supplierId, string $eventName, array $clientDtos): array
    {
        $clientIds = [];

        foreach ($clientDtos as $clientDto) {
            // Check if client already exists
            $client = $this->clientModel::where('email', $clientDto->email)
                ->where('supplier_id', $supplierId)
                ->first();

            if (!$client) {
                // Generate temporary password
                $temporaryPassword = Str::random(12);

                // Create new client
                $client = $this->clientModel::create([
                    'supplier_id' => $supplierId,
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
