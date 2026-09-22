<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Models\Event;
use App\Models\SupplierStaff;
use Illuminate\Support\Str;

readonly class EventClientService
{
    public function __construct(
        private SupplierStaff $authenticatedUser,
    ) {}

    /**
     * Attach clients to an event
     *
     * @param Event $event
     * @param array<string> $clientIds
     * @return void
     */
    public function attachClientsToEvent(Event $event, array $clientIds): void
    {
        foreach ($clientIds as $clientId) {
            $event->clients()->attach($clientId, [
                'id' => Str::uuid()->toString(),
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
