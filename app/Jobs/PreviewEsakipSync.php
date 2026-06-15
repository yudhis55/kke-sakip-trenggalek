<?php

namespace App\Jobs;

use App\Models\SyncProgress;
use App\Services\EsakipSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PreviewEsakipSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 7200;

    public function __construct(
        public readonly int $syncProgressId,
        public readonly int $tahunId,
        public readonly ?int $opdId = null,
        public readonly ?string $documentType = null,
    ) {}

    public function handle(EsakipSyncService $service): void
    {
        $syncProgress = SyncProgress::find($this->syncProgressId);

        if (! $syncProgress) {
            return;
        }

        if ($syncProgress->isCancelled()) {
            return;
        }

        $syncProgress->markAsProcessing();

        try {
            $previewData = $service->previewSync(
                $this->tahunId,
                $this->opdId,
                $this->documentType,
            );

            $syncProgress->markAsCompleted($previewData);

            Log::info('PreviewEsakipSync: Preview completed', [
                'sync_progress_id' => $this->syncProgressId,
                'document_count' => $previewData['document_count'] ?? 0,
            ]);
        } catch (Throwable $e) {
            $syncProgress->markAsFailed($e->getMessage());

            Log::error('PreviewEsakipSync: Preview failed', [
                'sync_progress_id' => $this->syncProgressId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $syncProgress = SyncProgress::find($this->syncProgressId);

        if ($syncProgress && $syncProgress->isRunning()) {
            $syncProgress->markAsFailed($exception->getMessage());
        }
    }
}