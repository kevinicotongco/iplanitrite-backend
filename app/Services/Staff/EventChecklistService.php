<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\AccountTemplateChecklistData;
use App\Data\EventChecklistAssigneeData;
use App\Dto\Request\EventChecklistUpdateAssigneeRequestDto;
use App\Dto\Request\SortRequestDto;
use App\Enums\AuditActionEnum;
use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventChecklistStatusEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Models\Event;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\Staff;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use LogicException;

readonly class EventChecklistService
{
    public function __construct(
        private EventChecklist $eventChecklistModel,
        private EventChecklistAssigneeService $eventChecklistAssigneeService,
        private SupplierService $supplierService,
        private AuditLogService $auditLogService,
    ) {}

    public function createChecklist(EventChecklistGroup $group, string $name): void
    {
        $maxSortOrder = $this->eventChecklistModel::where('event_checklist_group_id', $group->id)
            ->max('sort_order');

        $eventChecklist = $this->eventChecklistModel::create([
            'event_checklist_group_id' => $group->id,
            'name' => $name,
            'status' => EventChecklistStatusEnum::Pending,
            'sort_order' => $maxSortOrder !== null ? $maxSortOrder + 1 : 1,
        ]);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Create);
    }

    /**
     * @param EventChecklistGroup $group
     * @param array<int, SortRequestDto> $sortDtos
     * @return void
     */
    public function sortChecklists(EventChecklistGroup $group, array $sortDtos): void
    {
        foreach ($sortDtos as $sortDto) {
            $eventChecklist = $this->getChecklistForGroup($group, $sortDto->id);
            $eventChecklist->update(['sort_order' => $sortDto->sortOrder]);

            $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Sort);
        }
    }

    public function updateChecklistName(EventChecklistGroup $group, string $checklistId, string $name): void
    {
        $eventChecklist = $this->getChecklistForGroup($group, $checklistId);
        $eventChecklist->update(['name' => $name]);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Update);
    }

    public function updateChecklistDueDate(EventChecklistGroup $group, string $checklistId, ?Carbon $dueDate): void
    {
        $eventChecklist = $this->getChecklistForGroup($group, $checklistId);
        $eventChecklist->update(['due_date' => $dueDate]);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Update);
    }

    public function updateChecklistStatus(EventChecklistGroup $group, string $checklistId, EventChecklistStatusEnum $status): void
    {
        $eventChecklist = $this->getChecklistForGroup($group, $checklistId);
        $eventChecklist->update(['status' => $status]);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Update);
    }

    /**
     * @param Event $event
     * @param EventChecklistGroup $group
     * @param string $checklistId
     * @param array<int, EventChecklistUpdateAssigneeRequestDto> $assigneeDtos
     * @return Collection<int, EventChecklistAssigneeData>
     * @throws ValidationException
     */
    public function updateChecklistAssignees(
        Event $event,
        EventChecklistGroup $group,
        string $checklistId,
        array $assigneeDtos
    ): Collection {
        $eventChecklist = $this->getChecklistForGroup($group, $checklistId);

        $assignees = $this->eventChecklistAssigneeService->replaceAssignees($event, $eventChecklist, $assigneeDtos);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Update);

        return $assignees;
    }

    /**
     * @throws LogicException
     */
    public function updateChecklistSupplier(EventChecklistGroup $group, string $checklistId, string $supplierId): Supplier
    {
        $eventChecklist = $this->getChecklistForGroup($group, $checklistId);

        if ($group->checklist_type !== ChecklistGroupTypeEnum::Supplier) {
            throw new LogicException('Supplier can only be assigned to checklists in Supplier checklist groups.');
        }

        $supplier = $this->supplierService->getSupplierWithRelationsById($supplierId);

        $eventChecklist->update(['supplier_id' => $supplier->id]);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Update);

        return $supplier;
    }

    private function getChecklistForGroup(EventChecklistGroup $group, string $checklistId): EventChecklist
    {
        return $this->eventChecklistModel::where('id', $checklistId)
            ->where('event_checklist_group_id', $group->id)
            ->firstOrFail();
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
     * Create event checklist from template data and create assignees
     *
     * @param EventChecklistGroup $eventGroup
     * @param AccountTemplateChecklistData $templateChecklistData
     * @param string|Carbon $eventDate
     * @param string|null $staffId
     * @param array<string> $clientIds
     * @return EventChecklist
     */
    public function createChecklistFromTemplateData(
        EventChecklistGroup $eventGroup,
        AccountTemplateChecklistData $templateChecklistData,
        string|Carbon $eventDate,
        ?string $staffId,
        array $clientIds
    ): EventChecklist {
        $dueDate = null;
        if ($templateChecklistData->frequencyValue !== null &&
            $templateChecklistData->frequencyType !== null &&
            $templateChecklistData->frequencyAnchor !== null) {
            $dueDate = $this->calculateDueDate(
                $eventDate,
                $templateChecklistData->frequencyValue,
                $templateChecklistData->frequencyType,
                $templateChecklistData->frequencyAnchor
            );
        }

        $eventChecklist = $this->eventChecklistModel::create([
            'event_checklist_group_id' => $eventGroup->id,
            'name' => $templateChecklistData->name,
            'description' => $templateChecklistData->description,
            'due_date' => $dueDate,
            'sort_order' => $templateChecklistData->sortOrder,
            'supplier_id' => $templateChecklistData->supplierId,
        ]);

        // Log the create action
        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Create);

        // Create assignees based on responsibility type
        $this->eventChecklistAssigneeService->createAssigneesForChecklist(
            $eventChecklist,
            $templateChecklistData->responsibilityType,
            $staffId,
            $clientIds
        );

        return $eventChecklist;
    }

    /**
     * Calculate checklist due date based on event date, frequency, and anchor
     *
     * @param string|Carbon $eventDate
     * @param int $frequency
     * @param ChecklistFrequencyTypeEnum $frequencyType
     * @param FrequencyAnchorEnum $frequencyAnchor
     * @return Carbon|null
     */
    private function calculateDueDate(
        string|Carbon $eventDate,
        int $frequency,
        ChecklistFrequencyTypeEnum $frequencyType,
        FrequencyAnchorEnum $frequencyAnchor
    ): ?Carbon {
        try {
            $eventDateTime = $eventDate instanceof Carbon ? $eventDate : Carbon::parse($eventDate);

            if ($frequencyAnchor === FrequencyAnchorEnum::AfterCreation) {
                // AfterCreation: due_date = now() + frequency
                $baseDate = now();
                return match ($frequencyType) {
                    ChecklistFrequencyTypeEnum::Days => $baseDate->addDays($frequency),
                    ChecklistFrequencyTypeEnum::Weeks => $baseDate->addWeeks($frequency),
                    ChecklistFrequencyTypeEnum::Months => $baseDate->addMonths($frequency),
                };
            } else {
                // BeforeEvent: due_date = event_date - frequency
                return match ($frequencyType) {
                    ChecklistFrequencyTypeEnum::Days => $eventDateTime->subDays($frequency),
                    ChecklistFrequencyTypeEnum::Weeks => $eventDateTime->subWeeks($frequency),
                    ChecklistFrequencyTypeEnum::Months => $eventDateTime->subMonths($frequency),
                };
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
