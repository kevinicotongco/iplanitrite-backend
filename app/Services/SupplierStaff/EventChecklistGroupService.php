<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Models\Event;
use App\Models\EventChecklistGroup;
use App\Models\SupplierStaff;

readonly class EventChecklistGroupService
{
    public function __construct(
        private SupplierStaff $authenticatedUser,
        private EventChecklistGroup $eventChecklistGroupModel,
        private EventChecklistService $eventChecklistService,
        private SupplierTemplateChecklistGroupService $templateChecklistGroupService,
    ) {}

    /**
     * Copy supplier template checklists to event checklists
     *
     * @param Event $event
     * @param string $supplierId
     * @return void
     */
    public function copyTemplateChecklistsToEvent(Event $event, string $supplierId): void
    {
        // Get template checklist groups for this supplier and event type
        $templateGroups = $this->templateChecklistGroupService->getTemplateGroupsWithChecklists(
            $supplierId,
            $event->event_type
        );

        foreach ($templateGroups as $templateGroup) {
            // Create event checklist group
            $eventGroup = $this->eventChecklistGroupModel::create([
                'event_id' => $event->id,
                'name' => $templateGroup->name,
                'event_type' => $templateGroup->eventType,
                'created_by' => $this->authenticatedUser->id,
                'updated_by' => $this->authenticatedUser->id,
            ]);

            // Copy checklists from template to event
            foreach ($templateGroup->checklists as $templateChecklistData) {
                $this->eventChecklistService->createChecklistFromTemplateData(
                    $eventGroup,
                    $templateChecklistData,
                    $event->event_date
                );
            }
        }
    }
}
