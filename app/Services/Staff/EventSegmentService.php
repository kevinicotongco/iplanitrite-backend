<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Dto\Request\PrimaryEventSegmentRequestDto;
use App\Dto\Request\WeddingSegmentsRequestDto;
use App\Models\Event;
use App\Models\EventSegment;
use App\Services\AddressService;
use Exception;
use Illuminate\Database\QueryException;

readonly class EventSegmentService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private AddressService $addressService,
    ) {}

    /**
     * Create event segments based on event type
     * @param Event $event
     * @param WeddingSegmentsRequestDto|PrimaryEventSegmentRequestDto $segmentsDto
     * @param string $countryId
     * @return void
     * @throws Exception
     */
    public function createEventSegments(
        Event $event,
        WeddingSegmentsRequestDto|PrimaryEventSegmentRequestDto $segmentsDto,
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
     * @throws Exception
     */
    private function createWeddingSegments(Event $event, WeddingSegmentsRequestDto $dto, string $countryId): void
    {
        // Create Wedding segment (primary)
        $this->createSingleSegment($event, $dto->wedding, $countryId, 'Wedding', true);

        // Create Reception segment if provided (non-primary)
        if ($dto->reception) {
            $this->createSingleSegment($event, $dto->reception, $countryId, 'Reception', false);
        }
    }

    /**
     * Create a single event segment
     * @throws Exception
     */
    private function createSingleSegment(
        Event $event,
        PrimaryEventSegmentRequestDto $dto,
        string $countryId,
        string $name,
        bool $isPrimary = true
    ): void
    {
        // Check if a primary segment already exists for this event (only if creating a primary segment)
        if ($isPrimary) {
            $existingPrimarySegment = EventSegment::where('event_id', $event->id)
                ->where('is_primary', true)
                ->exists();

            if ($existingPrimarySegment) {
                throw new Exception('A primary segment already exists for this event. Only one primary segment is allowed per event.');
            }
        }

        // Create address for the segment
        $address = $this->addressService->createAddress($dto->address, $countryId);

        // Create the segment using DB::raw to ensure proper boolean casting for PostgreSQL
        try {
            EventSegment::create([
                'event_id' => $event->id,
                'name' => $name,
                'is_primary' => $isPrimary ? \DB::raw('true') : \DB::raw('false'),
                'date' => $dto->date,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'address_id' => $address->id,
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
            ]);
            return;
        } catch (QueryException $e) {
            // Check if the exception is due to the unique constraint violation
            if (str_contains($e->getMessage(), 'event_segments_event_id_is_primary_unique')) {
                throw new Exception('A primary segment already exists for this event. Only one primary segment is allowed per event.');
            }
            throw $e;
        }
    }
}
