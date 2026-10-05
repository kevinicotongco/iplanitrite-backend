<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

readonly class DocumentService
{
    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private Document $documentModel,
    ) {}

    public function uploadFile(UploadedFile $file): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = uniqid() . '_' . time() . '.' . $extension;

        $path = $file->storeAs('documents', $fileName, 'public');

        return $this->documentModel::create([
            'account_id' => $this->authenticatedUser->accountId,
            'name' => $fileName,
            'display_name' => $originalName,
            'url' => Storage::disk('public')->url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function getDocumentForAccount(string $documentId, string $field): Document
    {
        $document = $this->documentModel::where('id', $documentId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->first();

        if ($document === null) {
            throw ValidationException::withMessages([
                $field => 'The selected document is invalid.',
            ]);
        }

        return $document;
    }

    public function deleteFile(string $documentId): bool
    {
        $document = $this->documentModel::where('id', $documentId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->firstOrFail();

        $path = str_replace(Storage::disk('public')->url(''), '', $document->url);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return $document->delete();
    }
}
