<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Dto\Request\InitialEventSegmentRequestDto;
use App\Dto\Request\WeddingSegmentsRequestDto;
use App\Models\Event;
use App\Models\EventSegment;
use App\Services\AddressService;

readonly class EventSegmentService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private AddressService $addressService,
    ) {}

    /**
     * Create event segments based on event type
     *
     * @param Event $event
     * @param WeddingSegmentsRequestDto|InitialEventSegmentRequestDto $segmentsDto
     * @param string $countryId
     * @return void
     */
    public function createEventSegments(
        Event $event,
        WeddingSegmentsRequestDto|InitialEventSegmentRequestDto $segmentsDto,
        string $countryId
    ): void {
        if ($segmentsDto instanceof WeddingSegmentsRequestDto) {
            $this->createWeddingSegments($event, $segmentsDto, $countryId);
        } else {
            $this->createSingleSegment($event, $segmentsDto, $countryId, $event->event_type->value);
        }
    }

    /**
     * Create wedding segments (wedding and optional reception)
     */
    private function createWeddingSegments(Event $event, WeddingSegmentsRequestDto $dto, string $countryId): void
    {
        // Create Wedding segment
        $this->createSingleSegment($event, $dto->wedding, $countryId, 'Wedding');

        // Create Reception segment if provided
        if ($dto->reception) {
            $this->createSingleSegment($event, $dto->reception, $countryId, 'Reception');
        }
    }

    /**
     * Create a single event segment
     */
    private function createSingleSegment(
        Event $event,
        InitialEventSegmentRequestDto $dto,
        string $countryId,
        string $name
    ): EventSegment {
        // Create address for the segment
        $address = $this->addressService->createAddress($dto->address, $countryId);

        // Create the segment using DB::raw to ensure proper boolean casting for PostgreSQL
        return EventSegment::create([
            'event_id' => $event->id,
            'name' => $name,
            'is_primary' => \DB::raw('true'),
            'date' => $dto->date,
            'start_time' => $dto->startTime,
            'end_time' => $dto->endTime,
            'address_id' => $address->id,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }
}
