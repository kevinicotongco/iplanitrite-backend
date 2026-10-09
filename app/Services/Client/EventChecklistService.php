<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Enums\AuditActionEnum;
use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventChecklistStatusEnum;
use App\Models\Event;
use App\Models\EventChecklist;
use App\Models\Supplier;
use Illuminate\Validation\ValidationException;

readonly class EventChecklistService
{
    public function __construct(
        private EventChecklist $eventChecklistModel,
        private SupplierService $supplierService,
        private AuditLogService $auditLogService,
    ) {}

    public function updateChecklistStatus(Event $event, string $checklistId, EventChecklistStatusEnum $status): void
    {
        $eventChecklist = $this->getChecklistForEvent($event, $checklistId);
        $eventChecklist->update(['status' => $status]);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Update);
    }

    /**
     * @throws ValidationException
     */
    public function updateChecklistSupplier(Event $event, string $checklistId, ?string $supplierId): ?Supplier
    {
        $eventChecklist = $this->getChecklistForEvent($event, $checklistId);

        if ($eventChecklist->group->checklist_type !== ChecklistGroupTypeEnum::Supplier) {
            throw ValidationException::withMessages([
                'supplierId' => 'Supplier can only be assigned to checklists in Supplier checklist groups.',
            ]);
        }

        $supplier = null;

        if ($supplierId !== null) {
            $supplier = $this->supplierService->findSupplierForEvent($event, $supplierId);

            if ($supplier === null) {
                throw ValidationException::withMessages([
                    'supplierId' => 'The selected supplier is invalid.',
                ]);
            }
        }

        $eventChecklist->update(['supplier_id' => $supplier?->id]);

        $this->auditLogService->logEventChecklistAction($eventChecklist, AuditActionEnum::Update);

        return $supplier;
    }

    private function getChecklistForEvent(Event $event, string $checklistId): EventChecklist
    {
        return $this->eventChecklistModel::where('id', $checklistId)
            ->whereHas('group', fn($query) => $query->where('event_id', $event->id))
            ->with('group')
            ->firstOrFail();
    }
}
