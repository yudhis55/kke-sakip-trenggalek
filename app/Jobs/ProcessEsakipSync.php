<?php

namespace App\Jobs;

use App\Exceptions\SyncCancelledException;
use App\Models\SyncProgress;
use App\Services\EsakipSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEsakipSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan job. Internal retry per API call sudah ada di EsakipSyncService.
     */
    public int $tries = 1;

    /**
     * Timeout job dalam detik (1 jam — margin besar di atas estimasi ~16 menit).
     */
    public int $timeout = 3600;

    public function __construct(
        public readonly int $syncProgressId,
        public readonly int $tahunId,
        public readonly ?int $opdId = null,
        public readonly ?string $documentType = null,
    ) {}

    /**
     * Eksekusi job sinkronisasi di background.
     */
    public function handle(EsakipSyncService $service): void
    {
        $syncProgress = SyncProgress::find($this->syncProgressId);

        if (! $syncProgress) {
            Log::warning('ProcessEsakipSync: SyncProgress record not found', [
                'sync_progress_id' => $this->syncProgressId,
            ]);
            return;
        }

        // Jika sudah di-cancel sebelum job sempat jalan
        if ($syncProgress->isCancelled()) {
            Log::info('ProcessEsakipSync: Job cancelled before start', [
                'sync_progress_id' => $this->syncProgressId,
            ]);
            return;
        }

        $syncProgress->markAsProcessing();

        // Aktifkan delay 500ms antar API request untuk rate limiting
        $service->setDelayEnabled(true);

        try {
            $results = $service->processSync(
                $this->tahunId,
                $this->opdId,
                $this->documentType,
                function (int $current, int $total, string $message) use ($syncProgress): void {
                    // Update progress di database
                    $syncProgress->updateProgress($current, $total, $message);

                    // Cek apakah user membatalkan sync
                    // Fresh load untuk mendapatkan status terbaru dari DB
                    if ($syncProgress->fresh()->isCancelled()) {
                        throw new SyncCancelledException('Sinkronisasi dibatalkan oleh pengguna.');
                    }

                    // Rate limiting: delay 500ms antar step
                    // (delay per API call sudah di-handle oleh setDelayEnabled di service)
                }
            );

            $syncProgress->markAsCompleted($results);

            Log::info('ProcessEsakipSync: Sync completed successfully', [
                'sync_progress_id' => $this->syncProgressId,
                'success_count' => $results['success_count'] ?? 0,
                'failed_count' => $results['failed_count'] ?? 0,
            ]);
        } catch (SyncCancelledException $e) {
            // User membatalkan — jangan re-throw, ini bukan error
            $syncProgress->markAsCancelled();

            Log::info('ProcessEsakipSync: Sync cancelled by user', [
                'sync_progress_id' => $this->syncProgressId,
            ]);
        } catch (Throwable $e) {
            $syncProgress->markAsFailed($e->getMessage());

            Log::error('ProcessEsakipSync: Sync failed with exception', [
                'sync_progress_id' => $this->syncProgressId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Dipanggil Laravel jika job gagal setelah semua tries habis.
     */
    public function failed(Throwable $exception): void
    {
        $syncProgress = SyncProgress::find($this->syncProgressId);

        if ($syncProgress && $syncProgress->isRunning()) {
            $syncProgress->markAsFailed($exception->getMessage());
        }

        Log::error('ProcessEsakipSync: Job failed', [
            'sync_progress_id' => $this->syncProgressId,
            'error' => $exception->getMessage(),
        ]);
    }
}
