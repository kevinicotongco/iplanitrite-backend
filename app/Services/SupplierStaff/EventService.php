<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Dto\Request\AddressRequestDto;
use App\Dto\Request\CelebrantRequestDto;
use App\Dto\Request\ClientCreateRequestDto;
use App\Dto\Request\CreateEventRequestDto;
use App\Dto\Request\GetEventsRequestDto;
use App\Dto\Request\UpdateEventRequestDto;
use App\Enums\EventStatusEnum;
use App\Models\Address;
use App\Models\Celebrant;
use App\Models\Client;
use App\Models\ContactNumber;
use App\Models\Event;
use App\Notifications\ClientWelcomeNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class EventService
{
    public function __construct(
        private Event $eventModel,
        private Client $clientModel,
        private Celebrant $celebrantModel,
        private Address $addressModel,
        private ContactNumber $contactNumberModel,
    ) {}

    /**
     * Get filtered events for a supplier
     *
     * @param string $supplierId
     * @param GetEventsRequestDto $dto
     * @return Collection
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

        return $query->get();
    }

    /**
     * Create a new event with celebrants and clients
     *
     * @param string $supplierId
     * @param string $countryId
     * @param CreateEventRequestDto $dto
     * @param string $createdBy
     * @return Event
     */
    public function createEvent(string $supplierId, string $countryId, CreateEventRequestDto $dto, string $createdBy): Event
    {
        // Create celebrant one
        $celebrantOne = $this->createCelebrant($dto->celebrantOne, $countryId, $createdBy);

        // Create celebrant two if provided
        $celebrantTwo = null;
        if ($dto->celebrantTwo) {
            $celebrantTwo = $this->createCelebrant($dto->celebrantTwo, $countryId, $createdBy);
        }

        // Create event address
        $eventAddress = $this->createAddress($dto->address, $countryId);

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
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);

        // Create and attach clients
        if (!empty($dto->clients)) {
            $this->createAndAttachClients($event, $supplierId, $dto->clients, $createdBy);
        }

        return $event->load(['celebrantOne.address', 'celebrantOne.contactNumber', 'celebrantTwo.address', 'celebrantTwo.contactNumber', 'address']);
    }

    /**
     * Update an existing event
     *
     * @param string $eventId
     * @param string $supplierId
     * @param string $countryId
     * @param UpdateEventRequestDto $dto
     * @param string $updatedBy
     * @return Event
     */
    public function updateEvent(string $eventId, string $supplierId, string $countryId, UpdateEventRequestDto $dto, string $updatedBy): Event
    {
        $event = $this->eventModel::where('id', $eventId)
            ->where('supplier_id', $supplierId)
            ->firstOrFail();

        // Update celebrant one
        $this->updateCelebrant($event->celebrantOne, $dto->celebrantOne, $countryId, $updatedBy);

        // Update celebrant two
        if ($dto->celebrantTwo) {
            if ($event->celebrant_two_id) {
                $this->updateCelebrant($event->celebrantTwo, $dto->celebrantTwo, $countryId, $updatedBy);
            } else {
                $celebrantTwo = $this->createCelebrant($dto->celebrantTwo, $countryId, $updatedBy);
                $event->celebrant_two_id = $celebrantTwo->id;
            }
        } else {
            $event->celebrant_two_id = null;
        }

        // Update event address
        $this->updateAddress($event->address, $dto->address, $countryId);

        // Update event
        $event->update([
            'name' => $dto->name,
            'description' => $dto->description,
            'status' => $dto->status,
            'event_date' => $dto->eventDate,
            'updated_by' => $updatedBy,
        ]);

        return $event->fresh(['celebrantOne.address', 'celebrantOne.contactNumber', 'celebrantTwo.address', 'celebrantTwo.contactNumber', 'address']);
    }

    /**
     * Create a celebrant with optional address and contact number
     *
     * @param CelebrantRequestDto $dto
     * @param string $countryId
     * @param string $createdBy
     * @return Celebrant
     */
    private function createCelebrant(CelebrantRequestDto $dto, string $countryId, string $createdBy): Celebrant
    {
        $addressId = null;
        if ($dto->address) {
            $address = $this->createAddress($dto->address, $countryId);
            $addressId = $address->id;
        }

        $contactNumberId = null;
        if ($dto->contactNumber) {
            $contactNumber = $this->createContactNumber($dto->contactNumber, $countryId);
            $contactNumberId = $contactNumber->id;
        }

        return $this->celebrantModel::create([
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'address_id' => $addressId,
            'contact_number_id' => $contactNumberId,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }

    /**
     * Update a celebrant with optional address and contact number
     *
     * @param Celebrant $celebrant
     * @param CelebrantRequestDto $dto
     * @param string $countryId
     * @param string $updatedBy
     * @return void
     */
    private function updateCelebrant(Celebrant $celebrant, CelebrantRequestDto $dto, string $countryId, string $updatedBy): void
    {
        $addressId = null;
        if ($dto->address) {
            if ($celebrant->address_id) {
                $this->updateAddress($celebrant->address, $dto->address, $countryId);
                $addressId = $celebrant->address_id;
            } else {
                $address = $this->createAddress($dto->address, $countryId);
                $addressId = $address->id;
            }
        }

        $contactNumberId = null;
        if ($dto->contactNumber) {
            if ($celebrant->contact_number_id) {
                $celebrant->contactNumber->update([
                    'number' => $dto->contactNumber,
                    'country_id' => $countryId,
                ]);
                $contactNumberId = $celebrant->contact_number_id;
            } else {
                $contactNumber = $this->createContactNumber($dto->contactNumber, $countryId);
                $contactNumberId = $contactNumber->id;
            }
        }

        $celebrant->update([
            'first_name' => $dto->firstName,
            'middle_name' => $dto->middleName,
            'last_name' => $dto->lastName,
            'address_id' => $addressId,
            'contact_number_id' => $contactNumberId,
            'updated_by' => $updatedBy,
        ]);
    }

    /**
     * Create an address
     *
     * @param AddressRequestDto $dto
     * @param string $countryId
     * @return Address
     */
    private function createAddress(AddressRequestDto $dto, string $countryId): Address
    {
        return $this->addressModel::create([
            'line1' => $dto->line1,
            'line2' => $dto->line2,
            'city' => $dto->city,
            'state' => $dto->state,
            'zip' => $dto->zip,
            'lat' => $dto->lat,
            'long' => $dto->long,
            'country_id' => $countryId,
        ]);
    }

    /**
     * Update an address
     *
     * @param Address $address
     * @param AddressRequestDto $dto
     * @param string $countryId
     * @return void
     */
    private function updateAddress(Address $address, AddressRequestDto $dto, string $countryId): void
    {
        $address->update([
            'line1' => $dto->line1,
            'line2' => $dto->line2,
            'city' => $dto->city,
            'state' => $dto->state,
            'zip' => $dto->zip,
            'lat' => $dto->lat,
            'long' => $dto->long,
            'country_id' => $countryId,
        ]);
    }

    /**
     * Create a contact number
     *
     * @param string $number
     * @param string $countryId
     * @return ContactNumber
     */
    private function createContactNumber(string $number, string $countryId): ContactNumber
    {
        return $this->contactNumberModel::create([
            'number' => $number,
            'country_id' => $countryId,
        ]);
    }

    /**
     * Create clients and attach them to the event
     *
     * @param Event $event
     * @param string $supplierId
     * @param array<ClientCreateRequestDto> $clientDtos
     * @param string $createdBy
     * @return void
     */
    private function createAndAttachClients(Event $event, string $supplierId, array $clientDtos, string $createdBy): void
    {
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
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                ]);

                // Send welcome notification
                $client->notify(new ClientWelcomeNotification($temporaryPassword, $event->name));
            }

            // Attach client to event
            $event->clients()->attach($client->id, [
                'id' => Str::uuid()->toString(),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
