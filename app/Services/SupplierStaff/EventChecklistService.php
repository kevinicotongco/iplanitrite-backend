<?php

declare(strict_types=1);

namespace App\Services\SupplierStaff;

use App\Data\SupplierTemplateChecklistData;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\SupplierStaff;
use Carbon\Carbon;

readonly class EventChecklistService
{
    public function __construct(
        private SupplierStaff $authenticatedUser,
        private EventChecklist $eventChecklistModel,
    ) {}

    /**
     * Create event checklist from template data
     *
     * @param EventChecklistGroup $eventGroup
     * @param SupplierTemplateChecklistData $templateChecklistData
     * @param string|Carbon $eventDate
     * @return EventChecklist
     */
    public function createChecklistFromTemplateData(
        EventChecklistGroup $eventGroup,
        SupplierTemplateChecklistData $templateChecklistData,
        string|Carbon $eventDate
    ): EventChecklist {
        $dueDate = $this->calculateDueDate($eventDate, $templateChecklistData->frequencyDays);

        return $this->eventChecklistModel::create([
            'event_checklist_group_id' => $eventGroup->id,
            'name' => $templateChecklistData->name,
            'description' => $templateChecklistData->description,
            'due_date' => $dueDate,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    /**
     * Calculate checklist due date based on event date and frequency
     *
     * @param string|Carbon $eventDate
     * @param int $frequencyDays
     * @return Carbon|null
     */
    private function calculateDueDate(string|Carbon $eventDate, int $frequencyDays): ?Carbon
    {
        try {
            $eventDateTime = $eventDate instanceof Carbon ? $eventDate : Carbon::parse($eventDate);

            // Add or subtract days based on frequency
            // Positive = after event, Negative = before event
            return $eventDateTime->addDays($frequencyDays);
        } catch (\Exception $e) {
            return null;
        }
    }
}
