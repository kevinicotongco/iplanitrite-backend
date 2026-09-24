<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\EventChecklist;
use App\Models\EventChecklistLog;
use Illuminate\Support\Facades\Auth;

class EventChecklistObserver
{
    public function created(EventChecklist $eventChecklist): void
    {
        $this->createLog($eventChecklist, AuditActionEnum::Create);
    }

    public function updated(EventChecklist $eventChecklist): void
    {
        $this->createLog($eventChecklist, AuditActionEnum::Update);
    }

    public function deleted(EventChecklist $eventChecklist): void
    {
        $this->createLog($eventChecklist, AuditActionEnum::Delete);
    }

    private function createLog(EventChecklist $eventChecklist, AuditActionEnum $action): void
    {
        [$auditBy, $auditType] = $this->getAuditInfo();

        if ($auditBy === null) {
            return;
        }

        EventChecklistLog::create([
            'audit_by' => $auditBy,
            'audit_type' => $auditType->value,
            'audit_date' => now(),
            'event_checklist_id' => $eventChecklist->id,
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
