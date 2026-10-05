<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\EventChecklistGroupsByTypeData;
use App\Data\EventChecklistGroupWithChecklistsData;
use App\Dto\Request\CreateEventChecklistGroupRequestDto;
use App\Dto\Request\SortRequestDto;
use App\Enums\AuditActionEnum;
use App\Enums\ChecklistGroupTypeEnum;
use App\Models\Event;
use App\Models\EventChecklistGroup;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Relations\HasMany;

readonly class EventChecklistGroupService
{
    public function __construct(
        private EventChecklistGroup $eventChecklistGroupModel,
        private EventChecklistService $eventChecklistService,
        private AccountTemplateChecklistGroupService $templateChecklistGroupService,
        private AuditLogService $auditLogService,
    ) {}

    public function getGroupsByType(Event $event): EventChecklistGroupsByTypeData
    {
        $groups = $this->eventChecklistGroupModel::where('event_id', $event->id)
            ->orderBy('sort_order')
            ->with([
                'checklists' => fn(HasMany $query): HasMany => $query->orderBy('sort_order'),
                'checklists.supplier.contactNumber',
                'checklists.supplier.address.country',
                'checklists.assignees.staff',
                'checklists.assignees.client',
            ])
            ->get()
            ->map(fn(EventChecklistGroup $group): EventChecklistGroupWithChecklistsData => EventChecklistGroupWithChecklistsData::fromModel($group));

        return new EventChecklistGroupsByTypeData(
            supplier: $groups->filter(
                fn(EventChecklistGroupWithChecklistsData $group): bool => $group->checklistType === ChecklistGroupTypeEnum::Supplier
            )->values(),
            general: $groups->filter(
                fn(EventChecklistGroupWithChecklistsData $group): bool => $group->checklistType === ChecklistGroupTypeEnum::General
            )->values(),
        );
    }

    public function getGroupForEvent(Event $event, string $groupId): EventChecklistGroup
    {
        return $this->eventChecklistGroupModel::where('id', $groupId)
            ->where('event_id', $event->id)
            ->firstOrFail();
    }

    public function createGroup(Event $event, CreateEventChecklistGroupRequestDto $dto): void
    {
        $maxSortOrder = $this->eventChecklistGroupModel::where('event_id', $event->id)
            ->where('checklist_type', $dto->checklistType->value)
            ->max('sort_order');

        $group = $this->eventChecklistGroupModel::create([
            'event_id' => $event->id,
            'name' => $dto->name,
            'event_type' => $event->event_type,
            'checklist_type' => $dto->checklistType,
            'sort_order' => $maxSortOrder !== null ? $maxSortOrder + 1 : 1,
        ]);

        $this->auditLogService->logEventChecklistGroupAction($group, AuditActionEnum::Create);
    }

    /**
     * @param Event $event
     * @param array<int, SortRequestDto> $sortDtos
     * @return void
     */
    public function sortGroups(Event $event, array $sortDtos): void
    {
        foreach ($sortDtos as $sortDto) {
            $group = $this->getGroupForEvent($event, $sortDto->id);
            $group->update(['sort_order' => $sortDto->sortOrder]);

            $this->auditLogService->logEventChecklistGroupAction($group, AuditActionEnum::Sort);
        }
    }

    public function updateGroupName(Event $event, string $groupId, string $name): void
    {
        $group = $this->getGroupForEvent($event, $groupId);
        $group->update(['name' => $name]);

        $this->auditLogService->logEventChecklistGroupAction($group, AuditActionEnum::Update);
    }

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
     * @throws \Exception
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
                'checklist_type' => $templateGroup->checklistType,
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
