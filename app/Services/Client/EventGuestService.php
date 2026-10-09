<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\Auth\ClientAuthenticatedUser;
use App\Dto\Request\EventGuestCancelStatusRequestDto;
use App\Dto\Request\EventGuestCreateRequestDto;
use App\Dto\Request\EventGuestUpdateRequestDto;
use App\Dto\Request\SortRequestDto;
use App\Enums\EventGuestStatusEnum;
use App\Models\EventGuest;
use App\Models\EventGuestGroup;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

readonly class EventGuestService
{
    public function __construct(
        private ClientAuthenticatedUser $authenticatedUser,
        private EventGuest $eventGuestModel,
    ) {}

    /**
     * @param list<string> $eventGuestGroupIds
     * @return Collection<string, Collection<int, EventGuest>>
     */
    public function getGuestsGroupedByGroup(array $eventGuestGroupIds): Collection
    {
        return $this->eventGuestModel::whereIn('event_guest_group_id', $eventGuestGroupIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('event_guest_group_id');
    }

    public function createGuest(EventGuestGroup $group, EventGuestCreateRequestDto $dto): EventGuest
    {
        $maxSortOrder = $this->eventGuestModel::where('event_guest_group_id', $group->id)->max('sort_order');

        return $this->eventGuestModel::create([
            'event_guest_group_id' => $group->id,
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'sort_order' => $maxSortOrder !== null ? $maxSortOrder + 1 : 1,
            'status' => EventGuestStatusEnum::Pending,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    public function updateGuest(
        EventGuestGroup $group,
        string $eventGuestId,
        EventGuestUpdateRequestDto $dto,
        EventGuestGroup $targetGroup,
    ): EventGuest {
        $guest = $this->getGuestForGroup($group, $eventGuestId);

        $attributes = [
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'updated_by' => $this->authenticatedUser->id,
        ];

        if ($targetGroup->id !== $group->id) {
            $maxSortOrder = $this->eventGuestModel::where('event_guest_group_id', $targetGroup->id)->max('sort_order');

            $attributes['event_guest_group_id'] = $targetGroup->id;
            $attributes['sort_order'] = $maxSortOrder !== null ? $maxSortOrder + 1 : 1;
        }

        $guest->update($attributes);

        return $guest;
    }

    /**
     * @throws ValidationException
     */
    public function cancelGuest(EventGuestGroup $group, string $eventGuestId, EventGuestCancelStatusRequestDto $dto): EventGuest
    {
        $guest = $this->getGuestForGroup($group, $eventGuestId);

        if ($guest->status !== EventGuestStatusEnum::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Only approved guests can be cancelled.',
            ]);
        }

        $guest->update([
            'status' => EventGuestStatusEnum::Cancelled,
            'status_reason' => $dto->statusReason,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $guest;
    }

    /**
     * @throws ValidationException
     */
    public function deleteGuest(EventGuestGroup $group, string $eventGuestId): void
    {
        $guest = $this->getGuestForGroup($group, $eventGuestId);

        if ($guest->status === EventGuestStatusEnum::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Approved guests cannot be deleted.',
            ]);
        }

        $guest->delete();
    }

    /**
     * @param array<int, SortRequestDto> $sortDtos
     * @throws ValidationException
     */
    public function sortGuests(EventGuestGroup $group, array $sortDtos): void
    {
        $ids = array_map(fn(SortRequestDto $dto): string => $dto->id, $sortDtos);

        $guests = $this->eventGuestModel::where('event_guest_group_id', $group->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($sortDtos as $index => $sortDto) {
            if (!$guests->has($sortDto->id)) {
                $errors[$index . '.id'] = 'The selected guest is invalid.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($sortDtos as $sortDto) {
            $guests->get($sortDto->id)->update([
                'sort_order' => $sortDto->sortOrder,
                'updated_by' => $this->authenticatedUser->id,
            ]);
        }
    }

    private function getGuestForGroup(EventGuestGroup $group, string $eventGuestId): EventGuest
    {
        return $this->eventGuestModel::where('id', $eventGuestId)
            ->where('event_guest_group_id', $group->id)
            ->firstOrFail();
    }

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
