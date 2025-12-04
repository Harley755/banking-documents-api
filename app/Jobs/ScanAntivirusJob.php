<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanAntivirusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $document = Document::find($this->documentId);
        if (!$document) {
            return;
        }

        // Marquer comme en cours
        $document->status = 'scanning';
        $document->save();

        // Simuler un scan (dans un vrai cas, appeler ClamAV)
        sleep(1);

        // Simulation: marquer propre
        $document->status = 'clean';
        $document->scanned_at = now();
        $document->scan_result = 'OK (simulated)';
        $document->save();
    }
}
