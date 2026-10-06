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
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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
        private EventPackageService $eventPackageService,
        private EventPriceService $eventPriceService,
        private DocumentService $documentService,
        private EventFloorPlanService $eventFloorPlanService,
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
                'thumbnail',
                'package',
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
     * @throws Exception
     */
    public function createEvent(CreateEventRequestDto $dto): void
    {
        $eventPackage = $this->eventPackageService->getAssignablePackage($dto->eventPackageId, $dto->eventType);

        $this->assertThumbnailBelongsToAccount($dto->thumbnailId);

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
            'event_package_id' => $eventPackage->id,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        $this->eventPriceService->createInitialPrice($event, $eventPackage);

        $this->eventFloorPlanService->createDefaultFloorPlan($event);

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

        $isPackageChanged = $event->event_package_id !== $dto->eventPackageId;
        $eventPackage = $this->eventPackageService->getAssignablePackage(
            $dto->eventPackageId,
            $dto->eventType,
            allowDeleted: !$isPackageChanged,
        );

        $this->assertThumbnailBelongsToAccount($dto->thumbnailId);

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
            'event_package_id' => $eventPackage->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        if ($isPackageChanged) {
            $this->eventPriceService->syncPackageRetailPrice($event, $eventPackage);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertThumbnailBelongsToAccount(?string $thumbnailId): void
    {
        if ($thumbnailId !== null) {
            $this->documentService->getDocumentForAccount($thumbnailId, 'thumbnailId');
        }
    }

    public function getEventForAccount(string $eventId): Event
    {
        return $this->eventModel::where('id', $eventId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();
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
                'guestGroups.guests'
            ])
            ->firstOrFail();
    }
}
