<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\Auth\ClientAuthenticatedUser;
use App\Data\FloorPlanCanvasData;
use App\Dto\Request\EventSeatAssignmentRequestDto;
use App\Models\EventFloorPlan;
use App\Models\EventSeat;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

readonly class EventSeatService
{
    public function __construct(
        private ClientAuthenticatedUser $authenticatedUser,
        private EventSeat $eventSeatModel,
        private EventGuestService $eventGuestService,
    ) {}

    /**
     * @return Collection<int, EventSeat>
     */
    public function getSeatsForFloorPlan(EventFloorPlan $floorPlan): Collection
    {
        return $this->eventSeatModel::where('event_floor_plan_id', $floorPlan->id)
            ->orderBy('table_id')
            ->orderBy('seat_id')
            ->get();
    }

    /**
     * @param list<EventSeatAssignmentRequestDto> $assignments
     * @throws ValidationException
     */
    public function assignGuestsByTable(EventFloorPlan $floorPlan, string $tableId, array $assignments): void
    {
        $seatIds = $this->getTableSeatIds($floorPlan, $tableId);

        if (count($assignments) !== count($seatIds)) {
            throw ValidationException::withMessages([
                'guests' => 'The number of guests must match the number of seats of the table.',
            ]);
        }

        $guestIds = $this->extractGuestIds($assignments);

        $this->eventGuestService->assertGuestsBelongToEvent($floorPlan->event_id, $guestIds);

        $this->deleteSeats($floorPlan, tableId: $tableId);
        $this->deleteSeats($floorPlan, guestIds: $guestIds);

        shuffle($guestIds);

        foreach ($seatIds as $index => $seatId) {
            $this->createSeat($floorPlan, $tableId, $seatId, $guestIds[$index]);
        }
    }

    /**
     * @param list<EventSeatAssignmentRequestDto> $assignments
     * @throws ValidationException
     */
    public function assignGuestsBySeat(EventFloorPlan $floorPlan, string $tableId, array $assignments): void
    {
        $tableSeatIds = $this->getTableSeatIds($floorPlan, $tableId);

        $errors = [];

        foreach ($assignments as $index => $assignment) {
            if (!in_array($assignment->seatId, $tableSeatIds, true)) {
                $errors[$index . '.seatId'] = 'The selected seat does not belong to the table.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $guestIds = $this->extractGuestIds($assignments);

        $this->eventGuestService->assertGuestsBelongToEvent($floorPlan->event_id, $guestIds);

        $this->deleteSeats(
            $floorPlan,
            tableId: $tableId,
            seatIds: array_map(
                fn(EventSeatAssignmentRequestDto $assignment): string => $assignment->seatId,
                $assignments,
            ),
        );
        $this->deleteSeats($floorPlan, guestIds: $guestIds);

        foreach ($assignments as $assignment) {
            $this->createSeat($floorPlan, $tableId, $assignment->seatId, $assignment->eventGuestId);
        }
    }

    /**
     * @param list<EventSeatAssignmentRequestDto> $assignments
     * @return list<string>
     */
    private function extractGuestIds(array $assignments): array
    {
        return array_map(
            fn(EventSeatAssignmentRequestDto $assignment): string => $assignment->eventGuestId,
            $assignments,
        );
    }

    /**
     * @return list<string>
     */
    private function getTableSeatIds(EventFloorPlan $floorPlan, string $tableId): array
    {
        $table = FloorPlanCanvasData::fromArray($floorPlan->canvas_state)->findTable($tableId);

        if ($table === null) {
            abort(404);
        }

        return $table->seats;
    }

    /**
     * @param list<string>|null $guestIds
     * @param list<string>|null $seatIds
     */
    private function deleteSeats(
        EventFloorPlan $floorPlan,
        ?string $tableId = null,
        ?array $guestIds = null,
        ?array $seatIds = null,
    ): void {
        $query = $this->eventSeatModel::where('event_floor_plan_id', $floorPlan->id);

        if ($tableId !== null) {
            $query->where('table_id', $tableId);
        }

        if ($guestIds !== null) {
            $query->whereIn('event_guest_id', $guestIds);
        }

        if ($seatIds !== null) {
            $query->whereIn('seat_id', $seatIds);
        }

        $query->delete();
    }

    private function createSeat(EventFloorPlan $floorPlan, string $tableId, string $seatId, string $eventGuestId): void
    {
        $this->eventSeatModel::create([
            'event_floor_plan_id' => $floorPlan->id,
            'table_id' => $tableId,
            'seat_id' => $seatId,
            'event_guest_id' => $eventGuestId,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }
}
