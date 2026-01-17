<?php
// TEMPLATE: Method syncPenilaian yang baru untuk Smart Sync
// Copy method ini untuk replace method syncPenilaian di EsakipSyncService.php (line ~340-470)

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
        $penilaian = Penilaian::create([
            'opd_id' => $opd->id,
            'bukti_dukung_id' => $buktiDukung->id,
            'role_id' => $opdRole->id,
            'kriteria_komponen_id' => $buktiDukung->kriteria_komponen_id,
            'link_file' => $allFiles,
            'source' => 'esakip',
            'esakip_synced_at' => now(),
            'tingkatan_nilai_id' => $tingkatanNilaiId,
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
