<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\EventChecklistAssigneeData;
use App\Dto\Request\EventChecklistUpdateAssigneeRequestDto;
use App\Enums\EventChecklistAssigneeTypeEnum;
use App\Enums\ResponsibilityTypeEnum;
use App\Models\Event;
use App\Models\EventChecklist;
use App\Models\EventChecklistAssignee;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

readonly class EventChecklistAssigneeService
{
    public function __construct(
        private EventChecklistAssignee $eventChecklistAssigneeModel,
        private StaffManagementService $staffManagementService,
        private EventClientService $eventClientService,
    ) {}

    /**
     * Replace all assignees of a checklist with the given assignees
     *
     * @param Event $event
     * @param EventChecklist $eventChecklist
     * @param array<int, EventChecklistUpdateAssigneeRequestDto> $assigneeDtos
     * @return Collection<int, EventChecklistAssigneeData>
     * @throws ValidationException
     */
    public function replaceAssignees(Event $event, EventChecklist $eventChecklist, array $assigneeDtos): Collection
    {
        $this->validateAssignees($event, $assigneeDtos);

        $this->eventChecklistAssigneeModel::withTrashed()
            ->where('event_checklist_id', $eventChecklist->id)
            ->forceDelete();

        foreach ($assigneeDtos as $assigneeDto) {
            $this->createAssignee($eventChecklist->id, $assigneeDto->assigneeId, $assigneeDto->assigneeType);
        }

        return $this->eventChecklistAssigneeModel::where('event_checklist_id', $eventChecklist->id)
            ->with(['staff', 'client'])
            ->get()
            ->map(fn(EventChecklistAssignee $assignee): ?EventChecklistAssigneeData => EventChecklistAssigneeData::fromModel($assignee))
            ->filter()
            ->values();
    }

    /**
     * @param Event $event
     * @param array<int, EventChecklistUpdateAssigneeRequestDto> $assigneeDtos
     * @return void
     * @throws ValidationException
     */
    private function validateAssignees(Event $event, array $assigneeDtos): void
    {
        $staffIds = [];
        $clientIds = [];
        foreach ($assigneeDtos as $assigneeDto) {
            match ($assigneeDto->assigneeType) {
                EventChecklistAssigneeTypeEnum::Staff => $staffIds[] = $assigneeDto->assigneeId,
                EventChecklistAssigneeTypeEnum::Client => $clientIds[] = $assigneeDto->assigneeId,
            };
        }

        $validStaffIds = $this->staffManagementService->getStaffIdsInAccount($staffIds);
        $validClientIds = $this->eventClientService->getClientIdsAttachedToEvent($event, $clientIds);

        $errors = [];
        foreach ($assigneeDtos as $index => $assigneeDto) {
            $isValid = match ($assigneeDto->assigneeType) {
                EventChecklistAssigneeTypeEnum::Staff => in_array($assigneeDto->assigneeId, $validStaffIds, true),
                EventChecklistAssigneeTypeEnum::Client => in_array($assigneeDto->assigneeId, $validClientIds, true),
            };

            if (!$isValid) {
                $errors[$index . '.assigneeId'] = [
                    $assigneeDto->assigneeType === EventChecklistAssigneeTypeEnum::Staff
                        ? 'The selected staff does not belong to your account.'
                        : 'The selected client is not attached to this event.',
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Create assignees for an event checklist based on responsibility type
     *
     * @param EventChecklist $eventChecklist
     * @param ResponsibilityTypeEnum|null $responsibilityType
     * @param string|null $staffId
     * @param array<string> $clientIds
     * @return void
     */
    public function createAssigneesForChecklist(
        EventChecklist $eventChecklist,
        ?ResponsibilityTypeEnum $responsibilityType,
        ?string $staffId,
        array $clientIds
    ): void {
        if ($responsibilityType === null) {
            return;
        }

        // BOTH: assign to staff + all event clients
        if ($responsibilityType === ResponsibilityTypeEnum::Both) {
            if ($staffId) {
                $this->createAssignee(
                    $eventChecklist->id,
                    $staffId,
                    EventChecklistAssigneeTypeEnum::Staff
                );
            }

            foreach ($clientIds as $clientId) {
                $this->createAssignee(
                    $eventChecklist->id,
                    $clientId,
                    EventChecklistAssigneeTypeEnum::Client
                );
            }
        }
        // CLIENT: assign to all event clients
        elseif ($responsibilityType === ResponsibilityTypeEnum::Client) {
            foreach ($clientIds as $clientId) {
                $this->createAssignee(
                    $eventChecklist->id,
                    $clientId,
                    EventChecklistAssigneeTypeEnum::Client
                );
            }
        }
        // STAFF: assign to staff
        elseif ($responsibilityType === ResponsibilityTypeEnum::Staff) {
            if ($staffId) {
                $this->createAssignee(
                    $eventChecklist->id,
                    $staffId,
                    EventChecklistAssigneeTypeEnum::Staff
                );
            }
        }
    }

    /**
     * Create a single assignee record
     *
     * @param string $eventChecklistId
     * @param string $assigneeId
     * @param EventChecklistAssigneeTypeEnum $assigneeType
     * @return EventChecklistAssignee
     */
    private function createAssignee(
        string $eventChecklistId,
        string $assigneeId,
        EventChecklistAssigneeTypeEnum $assigneeType
    ): EventChecklistAssignee {
        return $this->eventChecklistAssigneeModel::create([
            'event_checklist_id' => $eventChecklistId,
            'assignee_id' => $assigneeId,
            'assignee_type' => $assigneeType->value,
        ]);
    }
}
