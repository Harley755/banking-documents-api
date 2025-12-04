<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Str;
use App\Models\DocumentShare;
use Illuminate\Support\Facades\Log;

class SharingService
{
    public function createShare(Document $document, int $userId, array $data): DocumentShare
    {
        $token = Str::random(64);

        Log::info("Max downloads: " . ($data['max_downloads'] ?? 100));

        $share = DocumentShare::create([
            'document_id' => $document->id,
            'token' => $token,
            'shared_with_email' => $data['shared_with_email'] ?? null,
            'expires_at' => $data['expires_at'] ?? now()->addHours(24),
            'max_downloads' => $data['max_downloads'] ?? 100,
            'download_count' => 0,
            'is_active' => true,
        ]);

        return $share;
    }

    public function getShareUrl(DocumentShare $share): string
    {
        // Appeler la route publique de partage
        return url('/api/documents/share/' . $share->token);
    }

    public function listDocumentShares(Document $document, int $userId)
    {
        return $document->shares()->where('is_active', true)->get();
    }

    /**
     * Trouve un partage par token
     */
    public function findByToken(string $token): ?DocumentShare
    {
        return DocumentShare::where('token', $token)->first();
    }

    /**
     * Enregistre un accès au partage (incrémente le compteur et gère la désactivation)
     * Lance une exception via abort() en cas d'invalidité
     */
    public function recordAccess(DocumentShare $share): void
    {
        Log::info("Is active ? " . ($share->is_active ? 'yes' : 'no'));
        if (!$share->is_active) {
            abort(410, 'Ce lien de partage a été désactivé');
        }

        if ($share->expires_at && $share->expires_at->isPast()) {
            // Désactiver le partage
            $share->is_active = false;
            $share->save();
            abort(410, 'Ce lien de partage a expiré');
        }

        if ($share->max_downloads > 0 && $share->download_count >= $share->max_downloads) {
            $share->is_active = false;
            $share->save();
            abort(410, 'Le nombre maximal de téléchargements a été atteint');
        }

        // Incrémenter de façon sûre
        $share->increment('download_count');

        // Si atteint la limite, désactiver
        if ($share->max_downloads > 0 && $share->download_count >= $share->max_downloads) {
            $share->is_active = false;
            $share->save();
        }
    }
}
