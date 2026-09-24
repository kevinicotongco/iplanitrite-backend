<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\AuditInfoData;
use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\Client;
use App\Models\EventChecklist;
use App\Models\EventChecklistGroup;
use App\Models\EventChecklistGroupLog;
use App\Models\EventChecklistLog;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierLog;

readonly class AuditLogService
{
    /**
     * Log a supplier action
     */
    public function logSupplierAction(Supplier $supplier, AuditActionEnum $action): void
    {
        $auditInfo = $this->getAuditInfo();

        SupplierLog::create([
            'audit_by' => $auditInfo->auditBy,
            'audit_type' => $auditInfo->auditType->value,
            'audit_date' => now(),
            'supplier_id' => $supplier->id,
            'action' => $action->value,
        ]);
    }

    /**
     * Log an event checklist group action
     */
    public function logEventChecklistGroupAction(EventChecklistGroup $group, AuditActionEnum $action): void
    {
        $auditInfo = $this->getAuditInfo();

        EventChecklistGroupLog::create([
            'audit_by' => $auditInfo->auditBy,
            'audit_type' => $auditInfo->auditType->value,
            'audit_date' => now(),
            'event_checklist_group_id' => $group->id,
            'action' => $action->value,
        ]);
    }

    /**
     * Log an event checklist action
     */
    public function logEventChecklistAction(EventChecklist $checklist, AuditActionEnum $action): void
    {
        $auditInfo = $this->getAuditInfo();

        EventChecklistLog::create([
            'audit_by' => $auditInfo->auditBy,
            'audit_type' => $auditInfo->auditType->value,
            'audit_date' => now(),
            'event_checklist_id' => $checklist->id,
            'action' => $action->value,
        ]);
    }

    /**
     * Get audit information from authenticated user
     */
    private function getAuditInfo(): AuditInfoData
    {
        $user = auth()->user();

        if ($user instanceof Staff) {
            return new AuditInfoData(
                auditBy: $user->id,
                auditType: AuditTypeEnum::Staff
            );
        }

        if ($user instanceof Client) {
            return new AuditInfoData(
                auditBy: $user->id,
                auditType: AuditTypeEnum::Client
            );
        }

        throw new \Exception('No authenticated user found for audit logging. Operations require authentication.');
    }
}
