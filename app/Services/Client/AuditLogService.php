<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Data\Auth\ClientAuthenticatedUser;
use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\EventChecklist;
use App\Models\EventChecklistLog;
use App\Models\Supplier;
use App\Models\SupplierLog;

readonly class AuditLogService
{
    public function __construct(
        private ClientAuthenticatedUser $authenticatedUser,
    ) {}

    public function logSupplierAction(Supplier $supplier, AuditActionEnum $action): void
    {
        SupplierLog::create([
            'audit_by' => $this->authenticatedUser->id,
            'audit_type' => AuditTypeEnum::Client->value,
            'audit_date' => now(),
            'supplier_id' => $supplier->id,
            'action' => $action->value,
        ]);
    }

    public function logEventChecklistAction(EventChecklist $checklist, AuditActionEnum $action): void
    {
        EventChecklistLog::create([
            'audit_by' => $this->authenticatedUser->id,
            'audit_type' => AuditTypeEnum::Client->value,
            'audit_date' => now(),
            'event_checklist_id' => $checklist->id,
            'action' => $action->value,
        ]);
    }
}
