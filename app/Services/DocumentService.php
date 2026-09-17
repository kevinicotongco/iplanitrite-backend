<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

readonly class DocumentService
{
    public function uploadFile(UploadedFile $file): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = uniqid() . '_' . time() . '.' . $extension;

        $path = $file->storeAs('documents', $fileName, 'public');

        $document = Document::create([
            'name' => $fileName,
            'display_name' => $originalName,
            'url' => Storage::disk('public')->url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return $document;
    }

    public function deleteFile(string $documentId): bool
    {
        $document = Document::findOrFail($documentId);

        $path = str_replace(Storage::disk('public')->url(''), '', $document->url);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return $document->delete();
    }
}
