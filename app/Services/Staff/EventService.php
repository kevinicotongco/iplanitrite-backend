<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Data\EventWithRelationsData;
use App\Dto\Request\CreateEventRequestDto;
use App\Dto\Request\GetEventsRequestDto;
use App\Dto\Request\UpdateEventRequestDto;
use App\Dto\Request\WeddingCelebrantsRequestDto;
use App\Enums\EventStatusEnum;
use App\Models\Account;
use App\Models\Client;
use App\Models\Event;
use App\Notifications\EventCreatedNotification;
use Illuminate\Support\Collection;

readonly class EventService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private Event $eventModel,
        private CelebrantService $celebrantService,
        private ClientService $clientService,
        private EventClientService $eventClientService,
        private EventChecklistGroupService $eventChecklistGroupService,
        private EventSegmentService $eventSegmentService,
    ) {}

    /**
     * @param GetEventsRequestDto $dto
     * @return Collection<EventWithRelationsData>
     */
    public function getEvents(GetEventsRequestDto $dto): Collection
    {
        $query = $this->eventModel::where('account_id', $this->authenticatedUser->accountId)
            ->with([
                'celebrantOne.address',
                'celebrantOne.contactNumber',
                'celebrantTwo.address',
                'celebrantTwo.contactNumber',
                'primarySegments.address',
                'thumbnail'
            ]);

        if ($dto->searchText) {
            $query->where('name', 'ilike', '%' . $dto->searchText . '%');
        }

        if ($dto->status) {
            $query->where('status', $dto->status);
        }

        $events = $query->get();

        return $events->map(fn($event) => EventWithRelationsData::fromModel($event));
    }

    /**
     * Create a new event with celebrants and clients
     *
     * @param CreateEventRequestDto $dto
     * @return void
     */
    public function createEvent(CreateEventRequestDto $dto): void
    {
        $account = Account::findOrFail($this->authenticatedUser->accountId);
        $countryId = $account->country_id;

        // Handle celebrants based on event type
        if ($dto->celebrants instanceof WeddingCelebrantsRequestDto) {
            // Wedding: create bride and groom
            $celebrantOne = $this->celebrantService->createCelebrant($dto->celebrants->bride, $countryId);
            $celebrantTwo = $this->celebrantService->createCelebrant($dto->celebrants->groom, $countryId);
        } else {
            // Non-wedding: single celebrant
            $celebrantOne = $this->celebrantService->createCelebrant($dto->celebrants, $countryId);
            $celebrantTwo = null;
        }

        // Create event (status defaults to Pending)
        $event = $this->eventModel::create([
            'account_id' => $this->authenticatedUser->accountId,
            'name' => $dto->name,
            'description' => $dto->description,
            'status' => EventStatusEnum::Pending,
            'event_type' => $dto->eventType,
            'celebrant_one_id' => $celebrantOne->id,
            'celebrant_two_id' => $celebrantTwo?->id,
            'thumbnail_id' => $dto->thumbnailId,
            'dress_code' => $dto->dressCode,
            'theme' => $dto->theme,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        // Create event segments
        $this->eventSegmentService->createEventSegments($event, $dto->segments, $countryId);

        // Create or get clients and attach to event
        $clientIds = [];
        if (!empty($dto->clients)) {
            $clientIds = $this->clientService->createOrGetClients($this->authenticatedUser->accountId, $dto->name, $dto->clients);
            $this->eventClientService->attachClientsToEvent($event, $clientIds);
        }

        // Copy template checklists to event checklists with assignees
        $this->eventChecklistGroupService->copyTemplateChecklistsToEvent($event, $this->authenticatedUser->accountId, $clientIds);

        // Send event created notifications to all clients
        $clients = Client::whereIn('id', $clientIds)->get();
        foreach ($clients as $client) {
            $client->notify(new EventCreatedNotification($event));
        }
    }

    /**
     * Update an existing event
     *
     * @param string $eventId
     * @param UpdateEventRequestDto $dto
     * @return void
     */
    public function updateEvent(string $eventId, UpdateEventRequestDto $dto): void
    {
        $event = $this->eventModel::where('id', $eventId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();

        $account = Account::findOrFail($this->authenticatedUser->accountId);
        $countryId = $account->country_id;

        // Handle celebrants based on event type
        if ($dto->celebrants instanceof WeddingCelebrantsRequestDto) {
            // Wedding: update bride and groom
            $this->celebrantService->updateCelebrant($event->celebrantOne, $dto->celebrants->bride, $countryId);

            if ($event->celebrant_two_id) {
                $this->celebrantService->updateCelebrant($event->celebrantTwo, $dto->celebrants->groom, $countryId);
            } else {
                $celebrantTwo = $this->celebrantService->createCelebrant($dto->celebrants->groom, $countryId);
                $event->celebrant_two_id = $celebrantTwo->id;
            }
        } else {
            // Non-wedding: update single celebrant
            $this->celebrantService->updateCelebrant($event->celebrantOne, $dto->celebrants, $countryId);
            $event->celebrant_two_id = null;
        }

        // Update event
        $event->update([
            'name' => $dto->name,
            'description' => $dto->description,
            'status' => $dto->status,
            'event_type' => $dto->eventType,
            'thumbnail_id' => $dto->thumbnailId,
            'dress_code' => $dto->dressCode,
            'theme' => $dto->theme,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    public function getEventById(string $eventId): Event
    {
        return $this->eventModel::where('id', $eventId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->with([
                'celebrantOne.address',
                'celebrantOne.contactNumber',
                'celebrantTwo.address',
                'celebrantTwo.contactNumber',
                'primarySegments.address',
                'thumbnail',
                'themeDocumentGroups.themeDocuments.document',
                'clients.address',
                'clients.contactNumber',
                'clients.profilePictureDocument',
                'guestGroups.guests'
            ])
            ->firstOrFail();
    }
}
