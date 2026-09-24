<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\Client;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierLog;
use Illuminate\Support\Facades\Auth;

class SupplierObserver
{
    public function created(Supplier $supplier): void
    {
        $this->createLog($supplier, AuditActionEnum::Create);
    }

    public function updated(Supplier $supplier): void
    {
        $this->createLog($supplier, AuditActionEnum::Update);
    }

    public function deleted(Supplier $supplier): void
    {
        $this->createLog($supplier, AuditActionEnum::Delete);
    }

    private function createLog(Supplier $supplier, AuditActionEnum $action): void
    {
        [$auditBy, $auditType] = $this->getAuditInfo();

        if ($auditBy === null) {
            return;
        }

        SupplierLog::create([
            'audit_by' => $auditBy,
            'audit_type' => $auditType->value,
            'audit_date' => now(),
            'supplier_id' => $supplier->id,
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
