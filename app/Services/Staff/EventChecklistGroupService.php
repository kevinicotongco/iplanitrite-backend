<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Models\Event;
use App\Models\EventChecklistGroup;
use App\Models\Staff;

readonly class EventChecklistGroupService
{
    public function __construct(
        private Staff $authenticatedUser,
        private EventChecklistGroup $eventChecklistGroupModel,
        private EventChecklistService $eventChecklistService,
        private AccountTemplateChecklistGroupService $templateChecklistGroupService,
    ) {}

    /**
     * @param Event $event
     * @param string $accountId
     * @return void
     */
    public function copyTemplateChecklistsToEvent(Event $event, string $accountId): void
    {
        // Get template checklist groups for this account and event type
        $templateGroups = $this->templateChecklistGroupService->getTemplateGroupsWithChecklists(
            $accountId,
            $event->event_type
        );

        // Get the primary event segment date for checklist calculations
        $primarySegment = $event->primarySegments()->first();
        $eventDate = $primarySegment ? $primarySegment->date : now();

        foreach ($templateGroups as $templateGroup) {
            // Create event checklist group
            $eventGroup = $this->eventChecklistGroupModel::create([
                'event_id' => $event->id,
                'name' => $templateGroup->name,
                'event_type' => $templateGroup->eventType,
                'sort_order' => $templateGroup->sortOrder,
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
            ]);

            // Copy checklists from template to event
            foreach ($templateGroup->checklists as $templateChecklistData) {
                $this->eventChecklistService->createChecklistFromTemplateData(
                    $eventGroup,
                    $templateChecklistData,
                    $eventDate
                );
            }
        }
    }
}
