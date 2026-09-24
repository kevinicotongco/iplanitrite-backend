<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Enums\AuditActionEnum;
use App\Models\Event;
use App\Models\EventChecklistGroup;
use App\Models\Staff;

readonly class EventChecklistGroupService
{
    public function __construct(
        private EventChecklistGroup $eventChecklistGroupModel,
        private EventChecklistService $eventChecklistService,
        private AccountTemplateChecklistGroupService $templateChecklistGroupService,
        private AuditLogService $auditLogService,
    ) {}

    /**
     * Get the authenticated staff user
     */
    private function getAuthenticatedUser(): Staff
    {
        $user = auth()->user();
        if (!$user instanceof Staff) {
            throw new \Exception('Authenticated user is not a Staff member');
        }
        return $user;
    }

    /**
     * @param Event $event
     * @param string $accountId
     * @param array<string> $clientIds
     * @return void
     */
    public function copyTemplateChecklistsToEvent(Event $event, string $accountId, array $clientIds): void
    {
        $authenticatedUser = $this->getAuthenticatedUser();

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
            ]);

            // Log the create action
            $this->auditLogService->logEventChecklistGroupAction($eventGroup, AuditActionEnum::Create);

            // Copy checklists from template to event with assignees
            foreach ($templateGroup->checklists as $templateChecklistData) {
                $this->eventChecklistService->createChecklistFromTemplateData(
                    $eventGroup,
                    $templateChecklistData,
                    $eventDate,
                    $authenticatedUser->id,
                    $clientIds
                );
            }
        }
    }
}
