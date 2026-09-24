<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\AccountTemplateChecklistData;
use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\Staff;
use Carbon\Carbon;

readonly class EventChecklistService
{
    public function __construct(
        private Staff $authenticatedUser,
        private EventChecklist $eventChecklistModel,
    ) {}

    /**
     * Create event checklist from template data
     *
     * @param EventChecklistGroup $eventGroup
     * @param AccountTemplateChecklistData $templateChecklistData
     * @param string|Carbon $eventDate
     * @return EventChecklist
     */
    public function createChecklistFromTemplateData(
        EventChecklistGroup $eventGroup,
        AccountTemplateChecklistData $templateChecklistData,
        string|Carbon $eventDate
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

        return $this->eventChecklistModel::create([
            'event_checklist_group_id' => $eventGroup->id,
            'name' => $templateChecklistData->name,
            'description' => $templateChecklistData->description,
            'due_date' => $dueDate,
            'sort_order' => $templateChecklistData->sortOrder,
            'supplier_id' => $templateChecklistData->supplierId,
            'responsibility_type' => $templateChecklistData->responsibilityType?->value,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
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
