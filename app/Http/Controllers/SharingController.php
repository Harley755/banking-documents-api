<?php

namespace App\Http\Controllers;

use App\Services\SharingService;
use App\Services\DocumentService;
use App\Enums\AuditAction;
use App\Models\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SharingController extends Controller
{
    public function __construct(
        private SharingService $sharingService,
        private DocumentService $documentService
    ) {}

    /**
     * GET /api/documents/share/{token}/info
     * Retourne les métadonnées publiques du partage
     */
    public function info(string $token, Request $request): JsonResponse
    {
        $share = $this->sharingService->findByToken($token);
        if (!$share) {
            return response()->json(['message' => 'Partage introuvable'], 404);
        }

        $document = $share->document;

        return response()->json([
            'data' => [
                'share' => [
                    'id' => $share->id,
                    'token' => $share->token,
                    'expires_at' => $share->expires_at?->toIso8601String(),
                    'max_downloads' => $share->max_downloads,
                    'download_count' => $share->download_count,
                    'is_active' => $share->is_active,
                ],
                'document' => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'original_filename' => $document->original_filename,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'document_type' => $document->document_type,
                    'status' => $document->status->value,
                ],
            ],
        ]);
    }

    /**
     * GET /api/documents/share/{token}
     * Télécharge le document via le token public
     */
    public function show(string $token, Request $request)
    {
        $share = $this->sharingService->findByToken($token);
        if (!$share) {
            return response()->json(['message' => 'Partage introuvable'], 404);
        }

        $this->sharingService->recordAccess($share);

        // Journaliser l'accès public
        Audit::log(
            AuditAction::SHARE_ACCESSED,
            $share,
            null,
            null,
            ['shared_with_email' => $share->shared_with_email]
        );

        $document = $share->document;

        $fileData = $this->documentService->downloadDocument($document, $document->user_id);

        return response($fileData['content'])
            ->header('Content-Type', $fileData['mime_type'])
            ->header('Content-Disposition', 'attachment; filename="' . $fileData['filename'] . '"');
    }
}
