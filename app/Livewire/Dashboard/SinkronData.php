<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\EsakipSyncService;
use App\Jobs\ProcessEsakipSync;
use App\Models\Tahun;
use App\Models\Opd;
use App\Models\RiwayatSinkron;
use App\Models\SyncProgress;
use Livewire\WithoutUrlPagination;

class SinkronData extends Component
{
    use WithPagination, WithoutUrlPagination;
    protected $paginationTheme = 'bootstrap';

    // Filter properties
    public $selected_tahun;
    public $selected_opd;
    public $selected_document_type;

    // Preview data
    public $previewData = null;

    // Progress tracking
    public $syncing = false;
    public $syncProgress = 0;
    public $syncMessage = '';
    public $syncResults = null;

    // Queue-based sync tracking
    public $activeSyncId = null;
    public $pollProgress = null;

    // Lists
    public $tahunList = [];
    public $opdList = [];
    public $documentTypes = [];

    protected $esakipService;

    public function boot(EsakipSyncService $esakipService)
    {
        $this->esakipService = $esakipService;
    }

    public function mount()
    {
        $this->tahunList = Tahun::orderBy('tahun', 'desc')->get();
        $this->opdList = Opd::orderBy('nama')->get();
        $this->documentTypes = config('esakip.document_types');

        // Cek apakah ada sync aktif saat halaman dimuat (misal setelah page refresh)
        $activeSync = SyncProgress::active()->latest()->first();
        if ($activeSync) {
            $this->activeSyncId = $activeSync->id;
            $this->syncing = true;
            $this->syncProgress = $activeSync->getProgressPercentage();
            $this->syncMessage = $activeSync->current_message ?? 'Sinkronisasi sedang berjalan...';
        }
    }

    /**
     * Preview dokumen yang akan di-sync
     */
    public function previewSync()
    {
        $this->validate([
            'selected_tahun' => 'required|exists:tahun,id',
        ], [
            'selected_tahun.required' => 'Tahun harus dipilih',
        ]);

        try {
            $this->previewData = $this->esakipService->previewSync(
                $this->selected_tahun,
                $this->selected_opd ?: null,
                $this->selected_document_type ?: null
            );

            if ($this->previewData['document_count'] === 0) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->warning('Tidak ada dokumen yang ditemukan untuk filter yang dipilih. Coba filter lain atau periksa data di E-SAKIP.');
                // Tetap tampilkan preview dengan info kosong, jangan set null
            }
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal preview: ' . $e->getMessage());
            $this->previewData = null;
        }
    }

    /**
     * Dispatch sinkronisasi ke background queue job.
     */
    public function processSync()
    {
        $this->validate([
            'selected_tahun' => 'required|exists:tahun,id',
        ]);

        // Tolak jika sudah ada sync aktif secara global
        if (SyncProgress::active()->exists()) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Sudah ada sinkronisasi yang sedang berjalan. Tunggu hingga selesai atau batalkan terlebih dahulu.');
            return;
        }

        // Buat record progress
        $syncProgress = SyncProgress::create([
            'tahun_id' => $this->selected_tahun,
            'opd_id' => $this->selected_opd ?: null,
            'document_type' => $this->selected_document_type ?: null,
            'status' => 'pending',
            'dispatched_by' => auth()->id(),
        ]);

        // Dispatch ke queue
        ProcessEsakipSync::dispatch(
            $syncProgress->id,
            (int) $this->selected_tahun,
            $this->selected_opd ? (int) $this->selected_opd : null,
            $this->selected_document_type ?: null,
        );

        $this->activeSyncId = $syncProgress->id;
        $this->syncing = true;
        $this->syncProgress = 0;
        $this->syncMessage = 'Sinkronisasi dijadwalkan, menunggu worker...';
        $this->syncResults = null;
        $this->previewData = null;

        flash()->use('theme.ruby')->option('position', 'bottom-right')->info('Sinkronisasi dimulai di background. Progress akan diperbarui otomatis.');
    }

    /**
     * Poll progress dari database (dipanggil via wire:poll setiap 3 detik).
     */
    public function pollSyncProgress()
    {
        if (! $this->activeSyncId) {
            // Cek apakah ada sync aktif yang mungkin dimulai dari tab lain
            $activeSync = SyncProgress::active()->latest()->first();
            if ($activeSync) {
                $this->activeSyncId = $activeSync->id;
                $this->syncing = true;
            } else {
                return;
            }
        }

        $syncProgress = SyncProgress::find($this->activeSyncId);

        if (! $syncProgress) {
            $this->syncing = false;
            $this->activeSyncId = null;
            return;
        }

        $this->syncProgress = $syncProgress->getProgressPercentage();
        $this->syncMessage = $syncProgress->current_message ?? '';

        if ($syncProgress->status === 'completed') {
            $this->syncing = false;
            $this->syncResults = $syncProgress->results ?? [];
            $this->activeSyncId = null;

            $results = $this->syncResults;
            if (! empty($results['success_count'])) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->success("Berhasil sinkronisasi {$results['success_count']} dokumen");
            }
            if (! empty($results['no_document_count'])) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->warning("{$results['no_document_count']} dokumen tidak ditemukan di esakip");
            }
            if (! empty($results['failed_count'])) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error("{$results['failed_count']} dokumen gagal disinkronkan");
            }
            if (! empty($results['skipped_count'])) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->info("{$results['skipped_count']} dokumen dilewati (sudah ada upload manual)");
            }
        } elseif ($syncProgress->status === 'failed') {
            $this->syncing = false;
            $this->activeSyncId = null;
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Sinkronisasi gagal: ' . ($syncProgress->error_message ?? 'Unknown error'));
        } elseif ($syncProgress->status === 'cancelled') {
            $this->syncing = false;
            $this->activeSyncId = null;
            flash()->use('theme.ruby')->option('position', 'bottom-right')->info('Sinkronisasi dibatalkan.');
        }
    }

    /**
     * Batalkan sync yang sedang berjalan.
     */
    public function cancelSync()
    {
        if (! $this->activeSyncId) {
            return;
        }

        $syncProgress = SyncProgress::find($this->activeSyncId);

        if ($syncProgress && $syncProgress->isRunning()) {
            $syncProgress->markAsCancelled();
            flash()->use('theme.ruby')->option('position', 'bottom-right')->info('Sinkronisasi dibatalkan. Proses akan berhenti pada langkah berikutnya.');
        }

        $this->syncing = false;
        $this->activeSyncId = null;
        $this->syncMessage = '';
        $this->syncProgress = 0;
    }

    /**
     * Reset form
     */
    public function resetForm()
    {
        $this->reset([
            'selected_tahun',
            'selected_opd',
            'selected_document_type',
            'previewData',
            'syncResults',
            'syncProgress',
            'syncMessage',
            'activeSyncId',
            'pollProgress',
        ]);
        $this->syncing = false;
    }

    /**
     * Get riwayat sinkronisasi terbaru
     */
    public function getRiwayatProperty()
    {
        return RiwayatSinkron::with(['opd', 'tahun'])
            ->orderBy('synced_at', 'desc')
            ->simplePaginate(5);
    }

    /**
     * Clear semua riwayat sinkronisasi
     */
    public function clearRiwayat()
    {
        try {
            RiwayatSinkron::truncate();
            flash()->use('theme.ruby')->option('position', 'bottom-right')->success('Riwayat sinkronisasi berhasil dibersihkan');
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal membersihkan riwayat: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.dashboard.sinkron-data');
    }
}
