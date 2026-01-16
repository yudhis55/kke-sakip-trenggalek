<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Services\EsakipSyncService;
use App\Models\Tahun;
use App\Models\Opd;
use App\Models\RiwayatSinkron;

class SinkronDokumen extends Component
{
    // Filter properties
    public $selected_tahun;
    public $selected_opd;
    public $selected_document_type;
    public $sync_mode = 'merge'; // merge, replace, skip

    // Preview data
    public $previewData = null;

    // Progress tracking
    public $syncing = false;
    public $syncProgress = 0;
    public $syncMessage = '';
    public $syncResults = null;

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
                flash()->use('theme.ruby')->option('position', 'bottom-right')->warning('Tidak ada dokumen yang ditemukan untuk filter yang dipilih');
                $this->previewData = null;
            }
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal preview: ' . $e->getMessage());
            $this->previewData = null;
        }
    }

    /**
     * Proses sinkronisasi
     */
    public function processSync()
    {
        $this->validate([
            'selected_tahun' => 'required|exists:tahun,id',
            'sync_mode' => 'required|in:merge,replace,skip',
        ]);

        $this->syncing = true;
        $this->syncProgress = 0;
        $this->syncMessage = 'Memulai sinkronisasi...';
        $this->syncResults = null;
        $this->previewData = null;

        try {
            $results = $this->esakipService->processSync(
                $this->selected_tahun,
                $this->selected_opd ?: null,
                $this->selected_document_type ?: null,
                $this->sync_mode,
                function ($current, $total, $message) {
                    $this->syncProgress = round(($current / $total) * 100);
                    $this->syncMessage = $message;
                    $this->dispatch('sync-progress', [
                        'progress' => $this->syncProgress,
                        'message' => $this->syncMessage,
                    ]);
                }
            );

            $this->syncResults = $results;
            $this->syncProgress = 100;
            $this->syncMessage = 'Sinkronisasi selesai!';

            // Flash message
            if ($results['success_count'] > 0) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->success("Berhasil sinkronisasi {$results['success_count']} dokumen");
            }
            if ($results['no_document_count'] > 0) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->warning("{$results['no_document_count']} dokumen tidak ditemukan di esakip");
            }
            if ($results['failed_count'] > 0) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->error("{$results['failed_count']} dokumen gagal disinkronkan");
            }
            if ($results['skipped_count'] > 0) {
                flash()->use('theme.ruby')->option('position', 'bottom-right')->info("{$results['skipped_count']} dokumen dilewati (sudah ada upload manual)");
            }
        } catch (\Exception $e) {
            flash()->use('theme.ruby')->option('position', 'bottom-right')->error('Gagal sinkronisasi: ' . $e->getMessage());
            $this->syncResults = [
                'error' => $e->getMessage(),
            ];
        } finally {
            $this->syncing = false;
        }
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
            'sync_mode',
            'previewData',
            'syncResults',
            'syncProgress',
            'syncMessage',
        ]);
        $this->sync_mode = 'merge';
    }

    /**
     * Get riwayat sinkronisasi terbaru
     */
    public function getRiwayatProperty()
    {
        return RiwayatSinkron::with(['opd', 'tahun'])
            ->orderBy('synced_at', 'desc')
            ->limit(20)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.sinkron-dokumen');
    }
}
