<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\Auth\ClientAuthenticatedUser;
use App\Data\EventGuestGroupWithGuestsData;
use App\Dto\Request\EventGuestGroupCreateRequestDto;
use App\Dto\Request\EventGuestGroupUpdateRequestDto;
use App\Dto\Request\SortRequestDto;
use App\Models\Event;
use App\Models\EventGuestGroup;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

readonly class EventGuestGroupService
{
    public function __construct(
        private ClientAuthenticatedUser $authenticatedUser,
        private EventGuestGroup $eventGuestGroupModel,
        private EventGuestService $eventGuestService,
    ) {}

    /**
     * @return Collection<int, EventGuestGroupWithGuestsData>
     */
    public function getGroupsWithGuests(Event $event): Collection
    {
        $groups = $this->eventGuestGroupModel::where('event_id', $event->id)
            ->orderBy('sort_order')
            ->get();

        $guestsByGroup = $this->eventGuestService->getGuestsGroupedByGroup($groups->pluck('id')->all());

        return $groups->map(fn(EventGuestGroup $group): EventGuestGroupWithGuestsData => new EventGuestGroupWithGuestsData(
            group: $group,
            guests: $guestsByGroup->get($group->id, collect()),
        ));
    }

    public function getGroupForEvent(Event $event, string $eventGuestGroupId): EventGuestGroup
    {
        return $this->eventGuestGroupModel::where('id', $eventGuestGroupId)
            ->where('event_id', $event->id)
            ->firstOrFail();
    }

    /**
     * @throws ValidationException
     */
    public function getGroupForEventInput(Event $event, string $eventGuestGroupId): EventGuestGroup
    {
        $group = $this->eventGuestGroupModel::where('id', $eventGuestGroupId)
            ->where('event_id', $event->id)
            ->first();

        if ($group === null) {
            throw ValidationException::withMessages([
                'eventGuestGroupId' => 'The selected guest group is invalid.',
            ]);
        }

        return $group;
    }

    public function createGroup(Event $event, EventGuestGroupCreateRequestDto $dto): EventGuestGroup
    {
        $maxSortOrder = $this->eventGuestGroupModel::where('event_id', $event->id)->max('sort_order');

        return $this->eventGuestGroupModel::create([
            'event_id' => $event->id,
            'name' => $dto->name,
            'sort_order' => $maxSortOrder !== null ? $maxSortOrder + 1 : 1,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    public function updateGroup(Event $event, string $eventGuestGroupId, EventGuestGroupUpdateRequestDto $dto): EventGuestGroup
    {
        $group = $this->getGroupForEvent($event, $eventGuestGroupId);

        $group->update([
            'name' => $dto->name,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $group;
    }

    /**
     * @param array<int, SortRequestDto> $sortDtos
     * @throws ValidationException
     */
    public function sortGroups(Event $event, array $sortDtos): void
    {
        $ids = array_map(fn(SortRequestDto $dto): string => $dto->id, $sortDtos);

        $groups = $this->eventGuestGroupModel::where('event_id', $event->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($sortDtos as $index => $sortDto) {
            if (!$groups->has($sortDto->id)) {
                $errors[$index . '.id'] = 'The selected guest group is invalid.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($sortDtos as $sortDto) {
            $groups->get($sortDto->id)->update([
                'sort_order' => $sortDto->sortOrder,
                'updated_by' => $this->authenticatedUser->id,
            ]);
        }
    }
}
