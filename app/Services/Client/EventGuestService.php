<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Models\EventGuest;
use Illuminate\Validation\ValidationException;

readonly class EventGuestService
{
    public function __construct(
        private EventGuest $eventGuestModel,
    ) {}

    /**
     * @param list<string> $eventGuestIds
     * @throws ValidationException
     */
    public function assertGuestsBelongToEvent(string $eventId, array $eventGuestIds): void
    {
        if ($eventGuestIds === []) {
            return;
        }

        $validIds = $this->eventGuestModel::whereIn('id', $eventGuestIds)
            ->whereHas('group', fn($query) => $query->where('event_id', $eventId))
            ->pluck('id')
            ->all();

        $errors = [];

        foreach ($eventGuestIds as $index => $eventGuestId) {
            if (!in_array($eventGuestId, $validIds, true)) {
                $errors[$index . '.eventGuestId'] = 'The selected guest is invalid.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
