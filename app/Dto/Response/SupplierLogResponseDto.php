<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\SupplierLog;
use Carbon\Carbon;

readonly class SupplierLogResponseDto
{
    public function __construct(
        public string $id,
        public string $auditBy,
        public AuditTypeEnum $auditType,
        public Carbon $auditDate,
        public string $supplierId,
        public AuditActionEnum $action,
    ) {}

    public static function fromModel(SupplierLog $log): self
    {
        return new self(
            id: $log->id,
            auditBy: $log->audit_by,
            auditType: $log->audit_type,
            auditDate: $log->audit_date,
            supplierId: $log->supplier_id,
            action: $log->action,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'auditBy' => $this->auditBy,
            'auditType' => $this->auditType->value,
            'auditDate' => $this->auditDate->toIso8601String(),
            'supplierId' => $this->supplierId,
            'action' => $this->action->value,
        ];
    }
}
