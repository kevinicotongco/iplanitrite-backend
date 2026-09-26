<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Enums\AuditActionEnum;
use App\Models\EventThemeDocument;

readonly class EventThemeDocumentService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private AuditLogService $auditLogService,
    ) {}

    public function createThemeDocument(string $groupId, string $documentId): EventThemeDocument
    {
        $themeDocument = EventThemeDocument::create([
            'event_theme_document_group_id' => $groupId,
            'document_id' => $documentId,
        ]);

        $this->auditLogService->logEventThemeDocument(
            $themeDocument->id,
            $this->authenticatedUser->id,
            $this->authenticatedUser->type,
            AuditActionEnum::Created
        );

        return $themeDocument;
    }

    public function deleteThemeDocument(string $themeDocumentId): void
    {
        $themeDocument = EventThemeDocument::findOrFail($themeDocumentId);

        $this->auditLogService->logEventThemeDocument(
            $themeDocument->id,
            $this->authenticatedUser->id,
            $this->authenticatedUser->type,
            AuditActionEnum::Deleted
        );

        $themeDocument->delete();
    }
}
