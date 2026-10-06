<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\Auth\ClientAuthenticatedUser;
use App\Models\Event;

readonly class EventService
{
    public function __construct(
        private ClientAuthenticatedUser $authenticatedUser,
        private Event $eventModel,
    ) {}

    public function getEventForClient(string $eventId): Event
    {
        return $this->eventModel::where('id', $eventId)
            ->whereHas('clients', fn($query) => $query
                ->where('clients.id', $this->authenticatedUser->id)
                ->whereNull('event_clients.deleted_at'))
            ->firstOrFail();
    }
}
