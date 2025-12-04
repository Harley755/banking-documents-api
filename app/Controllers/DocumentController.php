<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\DocumentService;
use App\Services\SharingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur de gestion des documents
 */
class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private SharingService $sharingService
    ) {}

    /**
     * POST /api/documents
     * Upload et création d'un nouveau document
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->createDocument(
            $request->file('file'),
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Document uploadé avec succès. Scan antivirus en cours.',
            'data' => [
                'id' => $document->id,
                'title' => $document->title,
                'original_filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'formatted_size' => $document->formatted_size,
                'document_type' => $document->document_type,
                'status' => $document->status->value,
                'status_label' => $document->status->label(),
                'created_at' => $document->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * GET /api/documents
     * Liste les documents de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentService->listDocuments(
            $request->user()->id,
            $request->only(['status', 'document_type'])
        );

        return response()->json([
            'data' => $documents->map(fn($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'description' => $doc->description,
                'original_filename' => $doc->original_filename,
                'mime_type' => $doc->mime_type,
                'file_size' => $doc->file_size,
                'formatted_size' => $doc->formatted_size,
                'document_type' => $doc->document_type,
                'status' => $doc->status->value,
                'status_label' => $doc->status->label(),
                'created_at' => $doc->created_at->toIso8601String(),
                'scanned_at' => $doc->scanned_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'last_page' => $documents->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/documents/{id}
     * Récupère les détails d'un document
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $document = $this->documentService->getDocument($id, $request->user()->id);

        return response()->json([
            'data' => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'original_filename' => $document->original_filename,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'formatted_size' => $document->formatted_size,
                'document_type' => $document->document_type,
                'status' => $document->status->value,
                'status_label' => $document->status->label(),
                'checksum' => $document->checksum,
                'created_at' => $document->created_at->toIso8601String(),
                'updated_at' => $document->updated_at->toIso8601String(),
                'scanned_at' => $document->scanned_at?->toIso8601String(),
                'scan_result' => $document->scan_result,
            ],
        ]);
    }

    /**
     * GET /api/documents/{id}/download
     * Télécharge un document
     */
    public function download(int $id, Request $request)
    {
        $document = Document::findOrFail($id);
        
        // Vérification via Policy
        $this->authorize('download', $document);

        $fileData = $this->documentService->downloadDocument(
            $document,
            $request->user()->id
        );

        return response($fileData['content'])
            ->header('Content-Type', $fileData['mime_type'])
            ->header('Content-Disposition', 'attachment; filename="' . $fileData['filename'] . '"');
    }

    /**
     * DELETE /api/documents/{id}
     * Supprime un document
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $document = Document::findOrFail($id);
        
        // Vérification via Policy
        $this->authorize('delete', $document);

        $this->documentService->deleteDocument($document, $request->user()->id);

        return response()->json([
            'message' => 'Document supprimé avec succès',
        ]);
    }

    /**
     * POST /api/documents/{id}/share
     * Crée un lien de partage temporaire
     */
    public function share(int $id, ShareDocumentRequest $request): JsonResponse
    {
        $document = Document::findOrFail($id);
        
        // Vérification via Policy
        $this->authorize('share', $document);

        $share = $this->sharingService->createShare(
            $document,
            $request->user()->id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Lien de partage créé avec succès',
            'data' => [
                'id' => $share->id,
                'token' => $share->token,
                'url' => $this->sharingService->getShareUrl($share),
                'expires_at' => $share->expires_at->toIso8601String(),
                'max_downloads' => $share->max_downloads,
                'download_count' => $share->download_count,
                'is_active' => $share->is_active,
            ],
        ], 201);
    }

    /**
     * GET /api/documents/{id}/shares
     * Liste les partages d'un document
     */
    public function shares(int $id, Request $request): JsonResponse
    {
        $document = Document::findOrFail($id);
        
        // Vérification via Policy
        $this->authorize('view', $document);

        $shares = $this->sharingService->listDocumentShares(
            $document,
            $request->user()->id
        );

        return response()->json([
            'data' => $shares->map(fn($share) => [
                'id' => $share->id,
                'token' => $share->token,
                'url' => $this->sharingService->getShareUrl($share),
                'shared_with_email' => $share->shared_with_email,
                'expires_at' => $share->expires_at->toIso8601String(),
                'max_downloads' => $share->max_downloads,
                'download_count' => $share->download_count,
                'is_active' => $share->is_active,
                'created_at' => $share->created_at->toIso8601String(),
            ]),
        ]);
    }
}