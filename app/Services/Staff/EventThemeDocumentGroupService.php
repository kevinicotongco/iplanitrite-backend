<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Enums\AuditActionEnum;
use App\Models\EventThemeDocumentGroup;

readonly class EventThemeDocumentGroupService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private AuditLogService $auditLogService,
    ) {}

    public function createDocumentGroup(string $eventId, string $name): EventThemeDocumentGroup
    {
        $group = EventThemeDocumentGroup::create([
            'event_id' => $eventId,
            'name' => $name,
        ]);

        $this->auditLogService->logEventThemeDocumentGroup(
            $group->id,
            $this->authenticatedUser->id,
            $this->authenticatedUser->type,
            AuditActionEnum::Created
        );

        return $group;
    }

    public function updateDocumentGroup(string $groupId, string $name): EventThemeDocumentGroup
    {
        $group = EventThemeDocumentGroup::findOrFail($groupId);

        $group->update([
            'name' => $name,
        ]);

        $this->auditLogService->logEventThemeDocumentGroup(
            $group->id,
            $this->authenticatedUser->id,
            $this->authenticatedUser->type,
            AuditActionEnum::Updated
        );

        return $group;
    }

    public function deleteDocumentGroup(string $groupId): void
    {
        $group = EventThemeDocumentGroup::findOrFail($groupId);

        $this->auditLogService->logEventThemeDocumentGroup(
            $group->id,
            $this->authenticatedUser->id,
            $this->authenticatedUser->type,
            AuditActionEnum::Deleted
        );

        $group->delete();
    }
}
