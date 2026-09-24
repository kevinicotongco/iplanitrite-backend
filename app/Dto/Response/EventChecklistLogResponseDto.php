<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use App\Models\EventChecklistLog;
use Carbon\Carbon;

readonly class EventChecklistLogResponseDto
{
    public function __construct(
        public string $id,
        public string $auditBy,
        public AuditTypeEnum $auditType,
        public Carbon $auditDate,
        public string $eventChecklistId,
        public AuditActionEnum $action,
    ) {}

    public static function fromModel(EventChecklistLog $log): self
    {
        return new self(
            id: $log->id,
            auditBy: $log->audit_by,
            auditType: $log->audit_type,
            auditDate: $log->audit_date,
            eventChecklistId: $log->event_checklist_id,
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
            'eventChecklistId' => $this->eventChecklistId,
            'action' => $this->action->value,
        ];
    }
}
