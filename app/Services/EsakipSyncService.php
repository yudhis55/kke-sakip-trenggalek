<?php

namespace App\Services;

use App\Models\BuktiDukung;
use App\Models\Penilaian;
use App\Models\RiwayatSinkron;
use App\Models\Tahun;
use App\Models\Opd;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EsakipSyncService
{
    protected $apiBaseUrl;
    protected $timeout;
    protected $retryCount;

    public function __construct()
    {
        $this->apiBaseUrl = config('esakip.api_base_url');
        $this->timeout = config('esakip.sync.timeout', 30);
        $this->retryCount = config('esakip.sync.retry_count', 3);
    }

    /**
     * Preview dokumen yang akan di-sync
     *
     * @param int $tahunId
     * @param int|null $opdId
     * @param string|null $documentType
     * @return array
     */
    public function previewSync($tahunId, $opdId = null, $documentType = null)
    {
        $tahun = Tahun::findOrFail($tahunId);

        // Get OPD list - FILTER hanya yang punya esakip_opd_id
        if ($opdId) {
            $opdList = Opd::where('id', $opdId)->whereNotNull('esakip_opd_id')->get();
        } else {
            $opdList = Opd::whereNotNull('esakip_opd_id')->get();
        }

        if ($opdList->isEmpty()) {
            throw new \Exception("Tidak ada OPD dengan mapping esakip_opd_id. Silakan isi esakip_opd_id di master OPD terlebih dahulu.");
        }

        // Get document types to sync
        $documentTypes = $documentType
            ? [$documentType => config("esakip.document_types.{$documentType}")]
            : config('esakip.document_types');

        $preview = [
            'tahun' => $tahun->tahun,
            'opd_count' => $opdList->count(),
            'document_count' => 0,
            'bukti_dukung_count' => 0,
            'auto_verified_count' => 0,
            'documents' => [],
            'errors' => [],
        ];

        foreach ($opdList as $opd) {
            foreach ($documentTypes as $type => $label) {
                // Get bukti dukung yang akan terisi
                $buktiDukungList = $this->getBuktiDukungForSync($tahunId, $type);

                if ($buktiDukungList->isEmpty()) {
                    continue; // Skip jika tidak ada mapping
                }

                // Simulasi call API untuk preview
                try {
                    $documents = $this->fetchDocumentsFromEsakip($type, $tahun->tahun, $opd->id);

                    // Fetch dan merge dokumen 'lainnya' yang keterangannya match
                    $lainnyaDocs = $this->fetchDocumentsFromEsakip('lainnya', $tahun->tahun, $opd->id);
                    $filteredLainnya = $this->filterLainnyaDocuments($lainnyaDocs, $type);

                    if (!empty($filteredLainnya)) {
                        $documents = array_merge($documents, $filteredLainnya);

                        Log::info("Preview: Merged 'lainnya' documents", [
                            'document_type' => $type,
                            'opd' => $opd->nama,
                            'lainnya_count' => count($filteredLainnya),
                            'total_documents' => count($documents),
                        ]);
                    }

                    if (empty($documents)) {
                        // Skip jika tidak ada dokumen, tapi tidak error
                        Log::info("No documents found", [
                            'type' => $type,
                            'opd' => $opd->nama,
                            'tahun' => $tahun->tahun,
                        ]);
                        continue;
                    }

                    foreach ($documents as $doc) {
                        $preview['document_count']++;
                        $preview['bukti_dukung_count'] += $buktiDukungList->count();

                        // Hitung auto-verified: bukti dukung yang punya esakip mapping
                        $autoVerifiedBukti = $buktiDukungList->filter(function ($bd) {
                            return $bd->is_auto_verified;
                        });
                        $preview['auto_verified_count'] += $autoVerifiedBukti->count();

                        $preview['documents'][] = [
                            'type' => $label,
                            'name' => $doc['keterangan'] ?? $doc['file'] ?? '-',
                            'opd' => $opd->nama,
                            'bukti_dukung_count' => $buktiDukungList->count(),
                            'auto_verify' => $autoVerifiedBukti->isNotEmpty(),
                            'file_url' => $doc['file_url'] ?? null,
                        ];
                    }
                } catch (\Exception $e) {
                    $errorMsg = "{$label} - {$opd->nama}: " . $e->getMessage();
                    Log::warning("Preview failed: " . $errorMsg);
                    $preview['errors'][] = $errorMsg;
                }
            }
        }

        // Jangan throw error jika document_count = 0, karena mungkin memang tidak ada data untuk filter tertentu
        // Biarkan user tahu bahwa preview berhasil dijalankan tapi tidak menemukan dokumen
        return $preview;
    }

    /**
     * Proses sinkronisasi dokumen
     *
     * @param int $tahunId
     * @param int|null $opdId
     * @param string|null $documentType
     * @param callable|null $progressCallback
     * @return array
     */
    public function processSync($tahunId, $opdId = null, $documentType = null, $progressCallback = null)
    {
        $tahun = Tahun::findOrFail($tahunId);

        // Filter OPD yang punya esakip_opd_id saja
        if ($opdId) {
            $opdList = Opd::where('id', $opdId)->whereNotNull('esakip_opd_id')->get();
        } else {
            $opdList = Opd::whereNotNull('esakip_opd_id')->get();
        }

        if ($opdList->isEmpty()) {
            throw new \Exception("Tidak ada OPD dengan mapping esakip_opd_id. Silakan isi esakip_opd_id di master OPD terlebih dahulu.");
        }

        $documentTypes = $documentType
            ? [$documentType => config("esakip.document_types.{$documentType}")]
            : config('esakip.document_types');

        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'no_document_count' => 0,
            'skipped_count' => 0,
            'total_penilaian' => 0,
            'auto_verified' => 0,
            'details' => [],
        ];

        $totalSteps = $opdList->count() * count($documentTypes);
        $currentStep = 0;

        foreach ($opdList as $opd) {
            foreach ($documentTypes as $type => $label) {
                $currentStep++;

                if ($progressCallback) {
                    $progressCallback($currentStep, $totalSteps, "Sync {$label} untuk {$opd->nama}...");
                }

                try {
                    $result = $this->syncDocumentForOpd($type, $label, $tahun, $opd);

                    // Aggregate results
                    if ($result['status'] === 'success') {
                        $results['success_count']++;
                    } elseif ($result['status'] === 'no_document') {
                        $results['no_document_count']++;
                    } elseif ($result['status'] === 'failed') {
                        $results['failed_count']++;
                    }

                    $results['skipped_count'] += $result['skipped_count'] ?? 0;
                    $results['total_penilaian'] += $result['affected_count'];
                    $results['auto_verified'] += $result['auto_verified_count'];
                    $results['details'][] = $result;
                } catch (\Exception $e) {
                    Log::error("Sync failed for {$type} - OPD {$opd->id}: " . $e->getMessage());
                    $results['failed_count']++;
                    $results['details'][] = [
                        'status' => 'failed',
                        'document_type' => $type,
                        'opd' => $opd->nama,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Sync satu jenis dokumen untuk satu OPD
     *
     * @param string $documentType
     * @param string $documentLabel
     * @param Tahun $tahun
     * @param Opd $opd
     * @return array
     */
    protected function syncDocumentForOpd($documentType, $documentLabel, $tahun, $opd)
    {
        // 1. Get bukti dukung yang di-mapping ke dokumen ini
        $buktiDukungList = $this->getBuktiDukungForSync($tahun->id, $documentType);

        if ($buktiDukungList->isEmpty()) {
            // Tidak ada mapping, skip
            return [
                'status' => 'no_document',
                'document_type' => $documentType,
                'opd' => $opd->nama,
                'message' => 'Tidak ada bukti dukung yang di-mapping',
                'affected_count' => 0,
                'auto_verified_count' => 0,
            ];
        }

        // 2. Fetch dokumen dari esakip
        $documents = $this->fetchDocumentsFromEsakip($documentType, $tahun->tahun, $opd->id);

        // 2.1. Fetch dokumen 'lainnya' yang keterangannya match dengan document_type
        $lainnyaDocs = $this->fetchDocumentsFromEsakip('lainnya', $tahun->tahun, $opd->id);
        $filteredLainnya = $this->filterLainnyaDocuments($lainnyaDocs, $documentType);

        // Merge dokumen 'lainnya' yang cocok ke documents utama
        if (!empty($filteredLainnya)) {
            $documents = array_merge($documents, $filteredLainnya);

            Log::info("Merged 'lainnya' documents", [
                'document_type' => $documentType,
                'opd' => $opd->nama,
                'lainnya_count' => count($filteredLainnya),
                'total_documents' => count($documents),
            ]);
        }

        // 2.2. Deteksi Dokumen Bersama (opd_id = 1)
        // Jika ada dokumen bersama, sync ke SEMUA OPD
        $isSharedDocument = false;
        foreach ($documents as $document) {
            if (isset($document['opd_id']) && $document['opd_id'] == 1) {
                $isSharedDocument = true;
                break;
            }
        }

        if ($isSharedDocument) {
            return $this->syncSharedDocument($documentType, $documentLabel, $tahun, $documents, $buktiDukungList);
        }

        if (empty($documents)) {
            // Tidak ada dokumen di esakip untuk OPD ini
            $this->logSync([
                'opd_id' => $opd->id,
                'tahun_id' => $tahun->id,
                'document_type' => $documentType,
                'document_name' => null,
                'tahun_value' => $tahun->tahun,
                'file_url' => null,
                'penilaian_ids' => [],
                'affected_count' => 0,
                'auto_verified_count' => 0,
                'status' => 'no_document',
                'synced_at' => now(),
            ]);

            return [
                'status' => 'no_document',
                'document_type' => $documentType,
                'opd' => $opd->nama,
                'message' => 'Tidak ada dokumen di esakip',
                'affected_count' => 0,
                'auto_verified_count' => 0,
            ];
        }

        // 3. Process setiap bukti dukung dengan semua dokumen (Smart Sync)
        $penilaianIds = [];
        $autoVerifiedCount = 0;
        $skippedCount = 0;
        $totalFilesAdded = 0;

        DB::beginTransaction();
        try {
            // Loop per bukti dukung (bukan per dokumen) untuk smart merge
            foreach ($buktiDukungList as $buktiDukung) {
                // Pass SEMUA dokumen sekaligus untuk smart merge
                $result = $this->syncPenilaian($buktiDukung, $opd, $documents);

                if ($result['status'] === 'created' || $result['status'] === 'updated') {
                    $penilaianIds[] = $result['penilaian_id'];
                    $totalFilesAdded += $result['files_added'] ?? 0;

                    if ($result['auto_verified'] ?? false) {
                        $autoVerifiedCount++;
                    }
                } elseif ($result['status'] === 'skipped') {
                    $skippedCount++;
                } elseif ($result['status'] === 'no_change') {
                    // Tidak ada perubahan, tapi tetap count sebagai processed
                    if (isset($result['penilaian_id'])) {
                        $penilaianIds[] = $result['penilaian_id'];
                    }
                }

                // Update bukti_dukung status
                $buktiDukung->update([
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                ]);
            }

            // 4. Log riwayat sinkronisasi
            $this->logSync([
                'opd_id' => $opd->id,
                'tahun_id' => $tahun->id,
                'document_type' => $documentType,
                'document_name' => $documentLabel,
                'tahun_value' => $tahun->tahun,
                'file_url' => null, // Multiple files, no single URL
                'penilaian_ids' => $penilaianIds,
                'affected_count' => count($penilaianIds),
                'auto_verified_count' => $autoVerifiedCount,
                'status' => 'success',
                'synced_at' => now(),
            ]);

            DB::commit();

            return [
                'status' => 'success',
                'document_type' => $documentType,
                'opd' => $opd->nama,
                'affected_count' => count($penilaianIds),
                'auto_verified_count' => $autoVerifiedCount,
                'skipped_count' => $skippedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Smart Sync penilaian dengan array dokumen dari E-SAKIP
     * NO MODE - otomatis menentukan tindakan berdasarkan kondisi
     *
     * @param BuktiDukung $buktiDukung
     * @param Opd $opd
     * @param array $documents - Array of documents from E-SAKIP API
     * @return array
     */
    protected function syncPenilaian($buktiDukung, $opd, $documents)
    {
        // PENTING: Dokumen dari esakip SELALU untuk role OPD
        $opdRole = \App\Models\Role::where('jenis', 'opd')->first();

        if (!$opdRole) {
            throw new \Exception('Role OPD tidak ditemukan. Pastikan ada role dengan jenis "opd".');
        }

        // Cek apakah penilaian OPD sudah ada
        $penilaian = Penilaian::where('opd_id', $opd->id)
            ->where('bukti_dukung_id', $buktiDukung->id)
            ->where('role_id', $opdRole->id) // HARUS role OPD
            ->first();

        // Inisialisasi tingkatanNilaiId untuk auto-verify
        $tingkatanNilaiId = null;

        // Calculate tingkatan nilai jika auto-verified
        if ($buktiDukung->is_auto_verified && $buktiDukung->kriteria_komponen) {
            $jenisNilaiId = $buktiDukung->kriteria_komponen->jenis_nilai_id;
            $tingkatanNilaiTertinggi = \App\Models\TingkatanNilai::where('jenis_nilai_id', $jenisNilaiId)
                ->orderBy('bobot', 'desc')
                ->first();
            $tingkatanNilaiId = $tingkatanNilaiTertinggi?->id;
        }

        // === CASE 1: Penilaian belum ada - CREATE NEW ===
        if (!$penilaian) {
            // Build all file objects dari API documents
            $allFiles = [];
            foreach ($documents as $doc) {
                $allFiles[] = $this->buildFileObject($doc);
            }

            // Create penilaian baru dengan semua dokumen
            // Role OPD: Upload dokumen + Input nilai (self-assessment)
            $penilaian = Penilaian::create([
                'opd_id' => $opd->id,
                'bukti_dukung_id' => $buktiDukung->id,
                'role_id' => $opdRole->id,
                'kriteria_komponen_id' => $buktiDukung->kriteria_komponen_id,
                'link_file' => $allFiles,
                'source' => 'esakip',
                'esakip_synced_at' => now(),
                'tingkatan_nilai_id' => $tingkatanNilaiId, // OPD punya nilai (self-assessment)
            ]);

            Log::info("Created new penilaian", [
                'penilaian_id' => $penilaian->id,
                'bukti_dukung_id' => $buktiDukung->id,
                'files_count' => count($allFiles),
            ]);

            // Auto-verify jika perlu
            if ($buktiDukung->is_auto_verified) {
                $this->createAutoVerifiedPenilaian($buktiDukung, $opd, $tingkatanNilaiId, $penilaian);
            }

            return [
                'status' => 'created',
                'penilaian_id' => $penilaian->id,
                'files_added' => count($allFiles),
                'auto_verified' => $buktiDukung->is_auto_verified,
            ];
        }

        // === CASE 2: Penilaian ada tapi source = 'upload' - SKIP (preserve user data) ===
        if ($penilaian->source === 'upload') {
            Log::info("Skipping sync - manual upload preserved", [
                'penilaian_id' => $penilaian->id,
                'bukti_dukung_id' => $buktiDukung->id,
            ]);

            return [
                'status' => 'skipped',
                'penilaian_id' => $penilaian->id,
                'reason' => 'manual_upload',
                'files_added' => 0,
                'auto_verified' => false,
            ];
        }

        // === CASE 3: Penilaian ada dan source = 'esakip' - SMART MERGE ===
        $existingFiles = $penilaian->link_file ?? [];
        $mergeResult = $this->smartMergeDocuments($existingFiles, $documents);

        $mergedFiles = $mergeResult['files'];
        $filesAdded = $mergeResult['added_count'];

        if ($filesAdded > 0) {
            // Ada dokumen baru, update
            $penilaian->update([
                'link_file' => $mergedFiles,
                'esakip_synced_at' => now(),
            ]);

            Log::info("Updated penilaian with new documents", [
                'penilaian_id' => $penilaian->id,
                'bukti_dukung_id' => $buktiDukung->id,
                'files_added' => $filesAdded,
                'total_files' => count($mergedFiles),
            ]);

            return [
                'status' => 'updated',
                'penilaian_id' => $penilaian->id,
                'files_added' => $filesAdded,
                'auto_verified' => false, // sudah ada sebelumnya
            ];
        }

        // Tidak ada perubahan
        Log::debug("No changes detected", [
            'penilaian_id' => $penilaian->id,
            'bukti_dukung_id' => $buktiDukung->id,
        ]);

        return [
            'status' => 'no_change',
            'penilaian_id' => $penilaian->id,
            'files_added' => 0,
            'auto_verified' => false,
        ];
    }


    /**
     * Create penilaian auto-verified untuk role verifikator sesuai bukti_dukung
     *
     * @param BuktiDukung $buktiDukung
     * @param Opd $opd
     * @param int|null $tingkatanNilaiId
     * @param Penilaian $penilaianOpd
     * @return void
     */
    protected function createAutoVerifiedPenilaian($buktiDukung, $opd, $tingkatanNilaiId, $penilaianOpd)
    {
        // Gunakan role_id dari bukti_dukung (bisa verifikator, penjamin, atau penilai)
        // Setiap bukti dukung punya petugas verifikasi sendiri
        $verifikatorRoleId = $buktiDukung->role_id;

        if (!$verifikatorRoleId) {
            Log::warning("Bukti dukung tidak memiliki role_id untuk verifikasi", [
                'bukti_dukung_id' => $buktiDukung->id,
                'nama' => $buktiDukung->nama,
            ]);
            return;
        }

        // Cek apakah penilaian verifikator sudah ada
        $existingVerifikator = Penilaian::where('opd_id', $opd->id)
            ->where('bukti_dukung_id', $buktiDukung->id)
            ->where('role_id', $verifikatorRoleId)
            ->first();

        if (!$existingVerifikator) {
            // Create penilaian auto-verified untuk role yang bertugas
            // PENTING: Verifikator TIDAK input nilai, hanya verifikasi (is_verified + keterangan)
            Penilaian::create([
                'opd_id' => $opd->id,
                'bukti_dukung_id' => $buktiDukung->id,
                'role_id' => $verifikatorRoleId,
                'kriteria_komponen_id' => $buktiDukung->kriteria_komponen_id,
                'tingkatan_nilai_id' => null, // Verifikator TIDAK input nilai
                'is_verified' => true,
                'keterangan' => 'Auto-verified dari sinkronisasi E-SAKIP',
                'source' => 'esakip',
                'esakip_synced_at' => now(),
            ]);

            Log::info("Created auto-verified penilaian", [
                'bukti_dukung_id' => $buktiDukung->id,
                'opd_id' => $opd->id,
                'role_id' => $verifikatorRoleId,
                'tingkatan_nilai_id' => null, // Verifikator tidak punya nilai
            ]);
        }
    }

    /**
     * Sync dokumen bersama ke SEMUA OPD
     *
     * @param string $documentType
     * @param string $documentLabel
     * @param Tahun $tahun
     * @param array $documents
     * @param \Illuminate\Database\Eloquent\Collection $buktiDukungList
     * @return array
     */
    protected function syncSharedDocument($documentType, $documentLabel, $tahun, $documents, $buktiDukungList)
    {
        $allOpds = Opd::all();
        $totalPenilaianIds = [];
        $totalAutoVerified = 0;
        $totalSkipped = 0;
        $totalFilesAdded = 0;

        // Filter hanya dokumen bersama (opd_id = 1)
        $sharedDocuments = array_filter($documents, function ($doc) {
            return isset($doc['opd_id']) && $doc['opd_id'] == 1;
        });

        if (empty($sharedDocuments)) {
            return [
                'status' => 'no_document',
                'document_type' => $documentType,
                'opd' => 'SEMUA OPD (Dokumen Bersama)',
                'message' => 'Tidak ada dokumen bersama',
                'affected_count' => 0,
            ];
        }

        DB::beginTransaction();
        try {
            foreach ($allOpds as $targetOpd) {
                // Loop per bukti dukung (Smart Sync)
                foreach ($buktiDukungList as $buktiDukung) {
                    // Pass SEMUA dokumen bersama sekaligus
                    $result = $this->syncPenilaian($buktiDukung, $targetOpd, $sharedDocuments);

                    if ($result['status'] === 'created' || $result['status'] === 'updated') {
                        $totalPenilaianIds[] = $result['penilaian_id'];
                        $totalFilesAdded += $result['files_added'] ?? 0;

                        if ($result['auto_verified'] ?? false) {
                            $totalAutoVerified++;
                        }
                    } elseif ($result['status'] === 'skipped') {
                        $totalSkipped++;
                    } elseif ($result['status'] === 'no_change') {
                        if (isset($result['penilaian_id'])) {
                            $totalPenilaianIds[] = $result['penilaian_id'];
                        }
                    }

                    // Update bukti_dukung status
                    $buktiDukung->update([
                        'sync_status' => 'synced',
                        'last_synced_at' => now(),
                    ]);
                }

                // Log untuk setiap OPD
                $this->logSync([
                    'opd_id' => $targetOpd->id,
                    'tahun_id' => $tahun->id,
                    'document_type' => $documentType,
                    'document_name' => $documentLabel,
                    'tahun_value' => $tahun->tahun,
                    'penilaian_ids' => $totalPenilaianIds,
                    'affected_count' => count($totalPenilaianIds),
                    'auto_verified_count' => $totalAutoVerified,
                    'status' => 'success',
                    'synced_at' => now(),
                ]);
            }

            DB::commit();

            return [
                'status' => 'success',
                'document_type' => $documentType,
                'opd' => 'SEMUA OPD (Dokumen Bersama)',
                'affected_count' => count($totalPenilaianIds),
                'auto_verified_count' => $totalAutoVerified,
                'skipped_count' => $totalSkipped,
                'shared_document' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Fetch dokumen dari API esakip
     *
     * @param string $documentType
     * @param int $tahun
     * @param int $opdId
     * @return array
     */
    /**
     * Fetch documents from E-SAKIP API (New Structure)
     *
     * @param string $documentType
     * @param int $tahun
     * @param int $opdId
     * @return array
     */
    protected function fetchDocumentsFromEsakip($documentType, $tahun, $opdId)
    {
        $endpoint = config('esakip.endpoints.document_base') . '/' . $documentType;

        // Get esakip_opd_id untuk mapping
        $opd = Opd::find($opdId);
        $esakipOpdId = $opd?->esakip_opd_id ?? $opdId;

        $url = $this->apiBaseUrl . $endpoint;

        // Log request details
        Log::info("Fetching from E-SAKIP", [
            'url' => $url,
            'document_type' => $documentType,
            'tahun' => $tahun,
            'opd_id' => $opdId,
            'esakip_opd_id' => $esakipOpdId,
            'opd_nama' => $opd?->nama,
        ]);

        try {
            $response = Http::connectTimeout(60) // Set connection timeout to 60 seconds
                ->timeout($this->timeout) // Set full request timeout
                ->withoutVerifying() // Disable SSL verification for development
                ->retry(5, 200) // Retry 5 times with 200ms delay (server is very slow)
                ->get($url, [
                    'tahun' => $tahun,
                    'opd' => $esakipOpdId, // Changed from opd_id to opd
                ]);

            // Log response status
            Log::info("E-SAKIP Response", [
                'status' => $response->status(),
                'success' => $response->successful(),
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // Log response structure
                Log::info("E-SAKIP Response Data", [
                    'has_data' => isset($result['data']),
                    'data_type' => isset($result['data']) ? gettype($result['data']) : 'null',
                    'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : [],
                    'message' => $result['message'] ?? null,
                ]);

                // Validate response structure
                if (!isset($result['data']) || empty($result['data'])) {
                    Log::warning("E-SAKIP returned empty data", [
                        'full_response' => $result,
                    ]);
                    return [];
                }

                $data = $result['data'];

                // Handle new nested structure: { "OPD Name": [...documents] }
                // Extract documents from nested object by iterating values
                $allDocuments = [];

                if (is_array($data) || is_object($data)) {
                    foreach ($data as $opdName => $documents) {
                        Log::info("Processing OPD documents", [
                            'opd_name_key' => $opdName,
                            'documents_count' => is_array($documents) ? count($documents) : 0,
                        ]);

                        if (is_array($documents)) {
                            foreach ($documents as $doc) {
                                // Filter by tahun for periode-type documents
                                if (isset($doc['jenis_periode']) && $doc['jenis_periode'] === 'periode') {
                                    if (isset($doc['periode']) && $this->isPeriodeMatchYear($doc['periode'], $tahun)) {
                                        $allDocuments[] = $this->normalizeDocument($doc);
                                    }
                                } else {
                                    // For tahun-type documents, include all (API already filters by tahun param)
                                    $allDocuments[] = $this->normalizeDocument($doc);
                                }
                            }
                        }
                    }
                }

                Log::info("E-SAKIP documents fetched", [
                    'total_documents' => count($allDocuments),
                ]);

                return $allDocuments;
            }

            Log::warning("API esakip failed: " . $response->status() . " - " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Failed to fetch from esakip: " . $e->getMessage(), [
                'exception' => $e,
                'url' => $url,
            ]);
            return [];
        }
    }

    /**
     * Check if periode range includes the given year
     * Example: "2021 - 2026" includes 2024
     *
     * @param string $periode
     * @param int $tahun
     * @return bool
     */
    protected function isPeriodeMatchYear($periode, $tahun)
    {
        // Parse "2021 - 2026" format
        if (preg_match('/(\d{4})\s*-\s*(\d{4})/', $periode, $matches)) {
            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];
            return $tahun >= $startYear && $tahun <= $endYear;
        }

        // If can't parse, return true to be safe
        return true;
    }

    /**
     * Normalize document structure from new API format
     *
     * @param array $doc
     * @return array
     */
    protected function normalizeDocument($doc)
    {
        return [
            'id' => $doc['opd_id'] ?? null,
            'opd_id' => $doc['opd_id'] ?? null,
            'opd_nama' => $doc['opd_nama'] ?? null,
            'file' => $doc['file'] ?? null,
            'file_url' => $doc['file'] ?? null, // file field already contains full URL
            'keterangan' => $doc['keterangan'] ?? null,
            'jenis_dokumen' => $doc['jenis_dokumen'] ?? null,
            'jenis_periode' => $doc['jenis_periode'] ?? null,
            'periode' => $doc['periode'] ?? null,
            'tanggal_publish' => $doc['tanggal_publish'] ?? null,
            'kategori' => $doc['kategori'] ?? null, // induk atau perubahan
            'page_number' => null, // New API doesn't provide page_number
        ];
    }

    /**
     * Filter dokumen 'lainnya' berdasarkan keterangan yang mengandung document_type
     *
     * @param array $lainnyaDocs - Dokumen dengan type 'lainnya' dari API
     * @param string $documentType - Jenis dokumen yang dicari (renstra, renja, dll)
     * @return array - Filtered documents yang keterangannya match
     */
    protected function filterLainnyaDocuments($lainnyaDocs, $documentType)
    {
        if (empty($lainnyaDocs)) {
            return [];
        }

        $filtered = [];

        // Normalize document_type: replace dash dengan spasi untuk matching
        // 'rencana-aksi' → 'rencana aksi'
        $normalizedType = str_replace('-', ' ', $documentType);

        foreach ($lainnyaDocs as $doc) {
            $keterangan = $doc['keterangan'] ?? '';

            // Check apakah keterangan mengandung normalized document_type (case-insensitive)
            // Coba match dengan versi normalized (pakai spasi) dan original (pakai dash)
            if (
                !empty($keterangan) &&
                (stripos($keterangan, $normalizedType) !== false || stripos($keterangan, $documentType) !== false)
            ) {
                // Mark bahwa ini dari 'lainnya' untuk tracking
                $doc['from_lainnya'] = true;
                $doc['matched_type'] = $documentType;

                $filtered[] = $doc;

                Log::info("Found matching 'lainnya' document", [
                    'keterangan' => $keterangan,
                    'matched_type' => $documentType,
                    'normalized_type' => $normalizedType,
                    'file' => $doc['file_url'] ?? $doc['file'] ?? 'unknown',
                ]);
            }
        }

        return $filtered;
    }

    /**     * Get bukti dukung yang di-mapping untuk jenis dokumen tertentu
     *
     * @param int $tahunId
     * @param string $documentType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getBuktiDukungForSync($tahunId, $documentType)
    {
        return BuktiDukung::where('tahun_id', $tahunId)
            ->where('esakip_document_type', $documentType)
            ->with(['role', 'kriteria_komponen'])
            ->get();
    }

    /**
     * Log riwayat sinkronisasi
     *
     * @param array $data
     * @return RiwayatSinkron
     */
    protected function logSync($data)
    {
        return RiwayatSinkron::create($data);
    }

    /**
     * Extract timestamp dari nama file E-SAKIP
     * Format: NamaFile_Periode_1761182369.pdf
     *
     * @param string $url
     * @return string|null
     */
    protected function extractTimestamp($url)
    {
        $filename = basename($url);

        // Extract timestamp (10 digit number) sebelum .pdf
        if (preg_match('/_(\d{10})\.pdf$/i', $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Build file object dengan metadata lengkap
     *
     * @param array $document
     * @return array
     */
    protected function buildFileObject($document)
    {
        $url = $document['file_url'] ?? $document['file'] ?? null;
        $timestamp = $this->extractTimestamp($url);
        $isPerubahan = isset($document['kategori']) && $document['kategori'] === 'perubahan';

        return [
            'url' => $url,
            'original_name' => basename($url ?? ''),
            'timestamp' => $timestamp,
            'kategori' => $document['kategori'] ?? 'induk',
            'tanggal_publish' => $document['tanggal_publish'] ?? null,
            'periode' => $document['periode'] ?? null,
            'keterangan' => $document['keterangan'] ?? null,
            'is_perubahan' => $isPerubahan,
            'from_esakip' => true,
            'from_lainnya' => $document['from_lainnya'] ?? false,
            'matched_type' => $document['matched_type'] ?? null,
            'synced_at' => now()->toDateTimeString(),
            'page_number' => null, // Page number per file, bukan per penilaian
        ];
    }

    /**
     * Check apakah dokumen sudah ada dalam link_file array
     * Compare by URL (primary) dan timestamp (secondary)
     *
     * @param array $linkFiles
     * @param array $documentToCheck
     * @return bool
     */
    protected function documentExists($linkFiles, $documentToCheck)
    {
        $checkUrl = $documentToCheck['url'] ?? null;
        $checkTimestamp = $documentToCheck['timestamp'] ?? null;

        if (!$checkUrl) {
            return false;
        }

        foreach ($linkFiles as $file) {
            $fileUrl = $file['url'] ?? null;
            $fileTimestamp = $file['timestamp'] ?? null;

            // Primary check: URL sama
            if ($fileUrl === $checkUrl) {
                return true;
            }

            // Secondary check: Timestamp sama (jika URL berubah tapi file sama)
            if ($checkTimestamp && $fileTimestamp && $checkTimestamp === $fileTimestamp) {
                return true;
            }
        }

        return false;
    }

    /**
     * Smart merge: Gabungkan existing files dengan dokumen baru dari API
     * Hanya tambahkan dokumen yang belum ada (no duplicate)
     *
     * @param array $existingFiles
     * @param array $apiDocuments
     * @return array
     */
    protected function smartMergeDocuments($existingFiles, $apiDocuments)
    {
        $mergedFiles = $existingFiles; // Start dengan existing files
        $addedCount = 0;

        foreach ($apiDocuments as $doc) {
            $fileObject = $this->buildFileObject($doc);

            // Check apakah dokumen sudah ada
            if (!$this->documentExists($mergedFiles, $fileObject)) {
                $mergedFiles[] = $fileObject;
                $addedCount++;

                Log::info("Adding new document", [
                    'url' => $fileObject['url'],
                    'timestamp' => $fileObject['timestamp'],
                    'kategori' => $fileObject['kategori'],
                ]);
            } else {
                Log::debug("Document already exists, skipping", [
                    'url' => $fileObject['url'],
                ]);
            }
        }

        return [
            'files' => $mergedFiles,
            'added_count' => $addedCount,
        ];
    }
}
