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
     * @param string $syncMode (merge, replace, skip)
     * @param callable|null $progressCallback
     * @return array
     */
    public function processSync($tahunId, $opdId = null, $documentType = null, $syncMode = 'merge', $progressCallback = null)
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
                    $result = $this->syncDocumentForOpd($type, $label, $tahun, $opd, $syncMode);

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
     * @param string $syncMode
     * @return array
     */
    protected function syncDocumentForOpd($documentType, $documentLabel, $tahun, $opd, $syncMode)
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

        // 2.1. Deteksi Dokumen Bersama (opd_id = 1)
        // Jika ada dokumen bersama, sync ke SEMUA OPD
        $isSharedDocument = false;
        foreach ($documents as $document) {
            if (isset($document['opd_id']) && $document['opd_id'] == 1) {
                $isSharedDocument = true;
                break;
            }
        }

        if ($isSharedDocument) {
            return $this->syncSharedDocument($documentType, $documentLabel, $tahun, $documents, $buktiDukungList, $syncMode);
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

        // 3. Process setiap dokumen
        $penilaianIds = [];
        $autoVerifiedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($documents as $document) {
                foreach ($buktiDukungList as $buktiDukung) {
                    $result = $this->syncPenilaian($buktiDukung, $opd, $document, $syncMode);

                    if ($result['status'] === 'synced') {
                        $penilaianIds[] = $result['penilaian_id'];
                        if ($result['auto_verified']) {
                            $autoVerifiedCount++;
                        }
                    } elseif ($result['status'] === 'skipped') {
                        $skippedCount++;
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
                    'document_name' => $document['keterangan'] ?? basename($document['file'] ?? ''),
                    'tahun_value' => $tahun->tahun,
                    'file_url' => $document['file_url'] ?? null,
                    'penilaian_ids' => $penilaianIds,
                    'affected_count' => count($penilaianIds),
                    'auto_verified_count' => $autoVerifiedCount,
                    'status' => 'success',
                    'synced_at' => now(),
                ]);
            }

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
     * Sync/Update satu penilaian
     *
     * @param BuktiDukung $buktiDukung
     * @param Opd $opd
     * @param array $document
     * @param string $syncMode
     * @return array
     */
    protected function syncPenilaian($buktiDukung, $opd, $document, $syncMode)
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

        $newFile = [
            'url' => $document['file_url'] ?? null,
            'original_name' => basename($document['file'] ?? ''),
            'from_esakip' => true,
            'synced_at' => now()->toDateTimeString(),
        ];

        if ($penilaian) {
            // Penilaian OPD sudah ada
            if ($penilaian->source === 'upload' && $syncMode === 'skip') {
                // Skip jika mode skip
                return [
                    'status' => 'skipped',
                    'penilaian_id' => $penilaian->id,
                    'auto_verified' => false,
                ];
            }

            if ($penilaian->source === 'upload' && $syncMode === 'merge') {
                // Merge: Append file baru
                $existingFiles = $penilaian->link_file ?? [];
                $penilaian->update([
                    'link_file' => array_merge($existingFiles, [$newFile]),
                    'esakip_document_id' => $document['id'] ?? null,
                    'esakip_synced_at' => now(),
                    'page_number' => $document['page_number'] ?? $penilaian->page_number,
                ]);
            } else {
                // Replace atau source sudah esakip
                $penilaian->update([
                    'link_file' => [$newFile],
                    'source' => 'esakip',
                    'esakip_document_id' => $document['id'] ?? null,
                    'esakip_synced_at' => now(),
                    'page_number' => $document['page_number'] ?? null,
                ]);
            }
        } else {
            // Jika auto_verified dan penilaian_di = 'bukti', ambil tingkatan_nilai dengan bobot tertinggi
            $tingkatanNilaiId = null;
            $shouldAutoVerify = $buktiDukung->is_auto_verified
                && $buktiDukung->kriteriaKomponen
                && $buktiDukung->kriteriaKomponen->penilaian_di === 'bukti';

            if ($shouldAutoVerify) {
                $jenisNilaiId = $buktiDukung->kriteriaKomponen->jenis_nilai_id;
                $tingkatanNilaiTertinggi = \App\Models\TingkatanNilai::where('jenis_nilai_id', $jenisNilaiId)
                    ->orderBy('bobot', 'desc')
                    ->first();
                $tingkatanNilaiId = $tingkatanNilaiTertinggi?->id;
            }

            // Buat penilaian baru untuk ROLE OPD
            $penilaian = Penilaian::create([
                'opd_id' => $opd->id,
                'bukti_dukung_id' => $buktiDukung->id,
                'role_id' => $opdRole->id, // SELALU role OPD
                'kriteria_komponen_id' => $buktiDukung->kriteria_komponen_id,
                'link_file' => [$newFile],
                'source' => 'esakip',
                'esakip_document_id' => $document['id'] ?? null,
                'esakip_synced_at' => now(),
                'page_number' => $document['page_number'] ?? null,
                'tingkatan_nilai_id' => $tingkatanNilaiId,
            ]);
        }

        // Auto-verify: Hanya jika penilaian_di = 'bukti' DAN is_auto_verified = true
        $shouldAutoVerify = $buktiDukung->is_auto_verified
            && $buktiDukung->kriteriaKomponen
            && $buktiDukung->kriteriaKomponen->penilaian_di === 'bukti';

        if ($shouldAutoVerify) {
            $this->createAutoVerifiedPenilaian($buktiDukung, $opd, $tingkatanNilaiId, $penilaian);
        }

        return [
            'status' => 'synced',
            'penilaian_id' => $penilaian->id,
            'auto_verified' => $buktiDukung->is_auto_verified,
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
            Penilaian::create([
                'opd_id' => $opd->id,
                'bukti_dukung_id' => $buktiDukung->id,
                'role_id' => $verifikatorRoleId,
                'kriteria_komponen_id' => $buktiDukung->kriteria_komponen_id,
                'tingkatan_nilai_id' => $tingkatanNilaiId,
                'is_verified' => true,
                'keterangan' => 'Auto-verified dari sinkronisasi E-SAKIP',
                'source' => 'esakip',
                'esakip_synced_at' => now(),
            ]);

            Log::info("Created auto-verified penilaian", [
                'bukti_dukung_id' => $buktiDukung->id,
                'opd_id' => $opd->id,
                'role_id' => $verifikatorRoleId,
                'tingkatan_nilai_id' => $tingkatanNilaiId,
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
     * @param string $syncMode
     * @return array
     */
    protected function syncSharedDocument($documentType, $documentLabel, $tahun, $documents, $buktiDukungList, $syncMode)
    {
        $allOpds = Opd::all();
        $totalPenilaianIds = [];
        $totalAutoVerified = 0;
        $totalSkipped = 0;

        DB::beginTransaction();
        try {
            foreach ($allOpds as $targetOpd) {
                foreach ($documents as $document) {
                    // Skip jika bukan dokumen bersama
                    if (!isset($document['opd_id']) || $document['opd_id'] != 1) {
                        continue;
                    }

                    foreach ($buktiDukungList as $buktiDukung) {
                        $result = $this->syncPenilaian($buktiDukung, $targetOpd, $document, $syncMode);

                        if ($result['status'] === 'synced') {
                            $totalPenilaianIds[] = $result['penilaian_id'];
                            if ($result['auto_verified']) {
                                $totalAutoVerified++;
                            }
                        } elseif ($result['status'] === 'skipped') {
                            $totalSkipped++;
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
                        'document_name' => $document['keterangan'] ?? basename($document['file'] ?? ''),
                        'tahun_value' => $tahun->tahun,
                        'file_url' => $document['file_url'] ?? null,
                        'penilaian_ids' => $totalPenilaianIds,
                        'affected_count' => count($totalPenilaianIds),
                        'auto_verified_count' => $totalAutoVerified,
                        'status' => 'success',
                        'synced_at' => now(),
                    ]);
                }
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
            'page_number' => null, // New API doesn't provide page_number
        ];
    }

    /**
     * Get bukti dukung yang di-mapping untuk jenis dokumen tertentu
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
}
