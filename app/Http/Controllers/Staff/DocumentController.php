<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Response\DocumentResponseDto;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\Document;
use App\Services\Staff\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class DocumentController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(UploadDocumentRequest $request, DocumentService $documentService): JsonResponse
    {
        $dto = $request->toDto();

        $document = DB::transaction(
            fn(): Document => $documentService->uploadFile($dto->file)
        );

        return response()->json(DocumentResponseDto::fromModel($document)->toArray(), 201);
    }
}
