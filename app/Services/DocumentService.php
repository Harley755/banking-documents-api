<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ScanAntivirusJob;

/**
 * Service minimal pour la gestion des documents (création, liste, téléchargement, suppression)
 * Implémentation allégée pour permettre au projet de fonctionner localement.
 */
class DocumentService
{
	public function createDocument(UploadedFile $file, array $data, int $userId): Document
	{
		$original = $file->getClientOriginalName();
		$mime = $file->getClientMimeType() ?? 'application/octet-stream';
		$size = $file->getSize() ?? 0;

		$contents = file_get_contents($file->getPathname());
		$checksum = hash('sha256', $contents);

		$encryptedFilename = Str::random(40) . '.' . $file->getClientOriginalExtension();

		// Stockage en local (private)
		Storage::put('private/documents/' . $encryptedFilename, $contents);

		// Création de l'entité Document
		$document = Document::create([
			'user_id' => $userId,
			'title' => $data['title'] ?? $original,
			'description' => $data['description'] ?? null,
			'original_filename' => $original,
			'encrypted_filename' => $encryptedFilename,
			'mime_type' => $mime,
			'file_size' => $size,
			'document_type' => $data['document_type'] ?? 'other',
			'status' => 'pending_scan',
			'checksum' => $checksum,
		]);

		// Dispatcher un job de scan antivirus asynchrone (simulé)
		ScanAntivirusJob::dispatch($document->id)->onQueue('default');

		return $document;
	}

	public function listDocuments(int $userId, array $filters = []): LengthAwarePaginator
	{
		$query = Document::where('user_id', $userId)->latest();

		if (!empty($filters['status'])) {
			$query->where('status', $filters['status']);
		}

		if (!empty($filters['document_type'])) {
			$query->where('document_type', $filters['document_type']);
		}

		return $query->paginate(15);
	}

	public function getDocument(int $id, int $userId): Document
	{
		return Document::where('id', $id)->where('user_id', $userId)->firstOrFail();
	}

	public function downloadDocument(Document $document, int $userId): array
	{
		$path = 'private/documents/' . $document->encrypted_filename;

		if (!Storage::exists($path)) {
			abort(404, 'Fichier introuvable');
		}

		$content = Storage::get($path);

		return [
			'content' => $content,
			'mime_type' => $document->mime_type,
			'filename' => $document->original_filename,
		];
	}

	public function deleteDocument(Document $document, int $userId): void
	{
		$path = 'private/documents/' . $document->encrypted_filename;

		if (Storage::exists($path)) {
			Storage::delete($path);
		}

		$document->delete();
	}
}
