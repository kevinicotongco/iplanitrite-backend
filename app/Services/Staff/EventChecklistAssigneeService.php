<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Enums\EventChecklistAssigneeTypeEnum;
use App\Enums\ResponsibilityTypeEnum;
use App\Models\EventChecklist;
use App\Models\EventChecklistAssignee;

readonly class EventChecklistAssigneeService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private EventChecklistAssignee $eventChecklistAssigneeModel,
    ) {}

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
