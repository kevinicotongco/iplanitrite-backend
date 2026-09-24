<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\EventChecklistGroup;
use App\Models\EventChecklistGroupLog;
use Illuminate\Support\Facades\Auth;

class EventChecklistGroupObserver
{
    public function created(EventChecklistGroup $eventChecklistGroup): void
    {
        $this->createLog($eventChecklistGroup, AuditActionEnum::Create);
    }

    public function updated(EventChecklistGroup $eventChecklistGroup): void
    {
        $this->createLog($eventChecklistGroup, AuditActionEnum::Update);
    }

    public function deleted(EventChecklistGroup $eventChecklistGroup): void
    {
        $this->createLog($eventChecklistGroup, AuditActionEnum::Delete);
    }

    private function createLog(EventChecklistGroup $eventChecklistGroup, AuditActionEnum $action): void
    {
        [$auditBy, $auditType] = $this->getAuditInfo();

        if ($auditBy === null) {
            return;
        }

        EventChecklistGroupLog::create([
            'audit_by' => $auditBy,
            'audit_type' => $auditType->value,
            'audit_date' => now(),
            'event_checklist_group_id' => $eventChecklistGroup->id,
            'action' => $action->value,
        ]);
    }

    /**
     * @return array{string|null, AuditTypeEnum|null}
     */
    private function getAuditInfo(): array
    {
        // Check for Staff (guard 'staff')
        if (Auth::guard('staff')->check()) {
            return [Auth::guard('staff')->id(), AuditTypeEnum::Staff];
        }

        // Check for Client (guard 'client')
        if (Auth::guard('client')->check()) {
            return [Auth::guard('client')->id(), AuditTypeEnum::Client];
        }

        return [null, null];
    }
}
