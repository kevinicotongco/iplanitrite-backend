<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Data\EventWithRelationsData;
use App\Dto\Request\CreateEventRequestDto;
use App\Dto\Request\GetEventsRequestDto;
use App\Dto\Request\UpdateEventRequestDto;
use App\Models\Event;
use App\Models\SupplierStaff;
use Illuminate\Support\Collection;

readonly class EventService
{
    public function __construct(
        private SupplierStaff $authenticatedUser,
        private Event $eventModel,
        private CelebrantService $celebrantService,
        private AddressService $addressService,
        private ClientService $clientService,
        private EventClientService $eventClientService,
        private EventChecklistGroupService $eventChecklistGroupService,
    ) {}

    /**
     * Get filtered events for a supplier
     *
     * @param string $supplierId
     * @param GetEventsRequestDto $dto
     * @return Collection<EventWithRelationsData>
     */
    public function getEvents(string $supplierId, GetEventsRequestDto $dto): Collection
    {
        $query = $this->eventModel::where('supplier_id', $supplierId)
            ->with(['celebrantOne.address', 'celebrantOne.contactNumber', 'celebrantTwo.address', 'celebrantTwo.contactNumber', 'address']);

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
     * @param string $supplierId
     * @param string $countryId
     * @param CreateEventRequestDto $dto
     * @return EventWithRelationsData
     */
    public function createEvent(string $supplierId, string $countryId, CreateEventRequestDto $dto): EventWithRelationsData
    {
        // Create celebrant one
        $celebrantOne = $this->celebrantService->createCelebrant($dto->celebrantOne, $countryId);

        // Create celebrant two if provided
        $celebrantTwo = null;
        if ($dto->celebrantTwo) {
            $celebrantTwo = $this->celebrantService->createCelebrant($dto->celebrantTwo, $countryId);
        }

        // Create event address
        $eventAddress = $this->addressService->createAddress($dto->address, $countryId);

        // Create event
        $event = $this->eventModel::create([
            'supplier_id' => $supplierId,
            'name' => $dto->name,
            'description' => $dto->description,
            'status' => $dto->status,
            'event_type' => $dto->eventType,
            'event_date' => $dto->eventDate,
            'celebrant_one_id' => $celebrantOne->id,
            'celebrant_two_id' => $celebrantTwo?->id,
            'address_id' => $eventAddress->id,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        // Create or get clients and attach to event
        if (!empty($dto->clients)) {
            $clientIds = $this->clientService->createOrGetClients($supplierId, $dto->name, $dto->clients);
            $this->eventClientService->attachClientsToEvent($event, $clientIds);
        }

        // Copy template checklists to event checklists
        $this->eventChecklistGroupService->copyTemplateChecklistsToEvent($event, $supplierId);

        $event->load(['celebrantOne.address', 'celebrantOne.contactNumber', 'celebrantTwo.address', 'celebrantTwo.contactNumber', 'address']);

        return EventWithRelationsData::fromModel($event);
    }

    /**
     * Update an existing event
     *
     * @param string $eventId
     * @param string $supplierId
     * @param string $countryId
     * @param UpdateEventRequestDto $dto
     * @return EventWithRelationsData
     */
    public function updateEvent(string $eventId, string $supplierId, string $countryId, UpdateEventRequestDto $dto): EventWithRelationsData
    {
        $event = $this->eventModel::where('id', $eventId)
            ->where('supplier_id', $supplierId)
            ->firstOrFail();

        // Update celebrant one
        $this->celebrantService->updateCelebrant($event->celebrantOne, $dto->celebrantOne, $countryId);

        // Update celebrant two
        if ($dto->celebrantTwo) {
            if ($event->celebrant_two_id) {
                $this->celebrantService->updateCelebrant($event->celebrantTwo, $dto->celebrantTwo, $countryId);
            } else {
                $celebrantTwo = $this->celebrantService->createCelebrant($dto->celebrantTwo, $countryId);
                $event->celebrant_two_id = $celebrantTwo->id;
            }
        } else {
            $event->celebrant_two_id = null;
        }

        // Update event address
        $this->addressService->updateAddress($event->address, $dto->address, $countryId);

        // Update event
        $event->update([
            'name' => $dto->name,
            'description' => $dto->description,
            'status' => $dto->status,
            'event_date' => $dto->eventDate,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        $event = $event->fresh(['celebrantOne.address', 'celebrantOne.contactNumber', 'celebrantTwo.address', 'celebrantTwo.contactNumber', 'address']);

        return EventWithRelationsData::fromModel($event);
    }


}
