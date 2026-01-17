<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VALIDASI DETAIL SINKRONISASI ===\n\n";

// 1. Total Penilaian
$opdRole = \App\Models\Role::where('jenis', 'opd')->first();
$verifRole = \App\Models\Role::where('jenis', 'verifikator')->first();

$opdCount = \App\Models\Penilaian::where('source', 'esakip')
    ->where('role_id', $opdRole->id)
    ->count();

$verifCount = \App\Models\Penilaian::where('source', 'esakip')
    ->where('role_id', $verifRole->id)
    ->count();

echo "1. TOTAL PENILAIAN:\n";
echo "   - OPD: {$opdCount}\n";
echo "   - Verifikator: {$verifCount}\n";
echo "   - Total: " . ($opdCount + $verifCount) . "\n\n";

// 2. Sample OPD Penilaian dengan Tingkatan Nilai
echo "2. SAMPLE PENILAIAN OPD (dengan Tingkatan Nilai):\n";
$opdSamples = \App\Models\Penilaian::where('source', 'esakip')
    ->where('role_id', $opdRole->id)
    ->with(['bukti_dukung', 'tingkatan_nilai.jenis_nilai'])
    ->take(5)
    ->get();

foreach ($opdSamples as $p) {
    $buktiNama = $p->bukti_dukung->nama;
    if ($p->tingkatan_nilai) {
        $nilaiNama = $p->tingkatan_nilai->nama;
        $bobot = $p->tingkatan_nilai->bobot;
        $jenisNilai = $p->tingkatan_nilai->jenis_nilai->nama ?? 'N/A';
        echo "   ✓ {$buktiNama}\n";
        echo "     Tingkatan: {$nilaiNama} (Bobot: {$bobot})\n";
        echo "     Jenis: {$jenisNilai}\n";
    } else {
        echo "   ✗ {$buktiNama} - TIDAK ADA TINGKATAN NILAI!\n";
    }
}

// 3. Validasi semua OPD penilaian punya tingkatan_nilai_id
$opdWithoutNilai = \App\Models\Penilaian::where('source', 'esakip')
    ->where('role_id', $opdRole->id)
    ->whereNull('tingkatan_nilai_id')
    ->count();

echo "\n3. VALIDASI OPD PENILAIAN:\n";
if ($opdWithoutNilai > 0) {
    echo "   ✗ MASALAH: {$opdWithoutNilai} penilaian OPD TIDAK memiliki tingkatan_nilai_id\n";
} else {
    echo "   ✓ Semua {$opdCount} penilaian OPD memiliki tingkatan_nilai_id\n";
}

// 4. Sample Verifikator Penilaian
echo "\n4. SAMPLE PENILAIAN VERIFIKATOR (Auto-Verified):\n";
$verifSamples = \App\Models\Penilaian::where('source', 'esakip')
    ->where('role_id', $verifRole->id)
    ->with('bukti_dukung')
    ->take(5)
    ->get();

foreach ($verifSamples as $p) {
    $buktiNama = $p->bukti_dukung->nama;
    $verified = $p->is_verified ? 'YES' : 'NO';
    $tingkatan = $p->tingkatan_nilai_id ?? 'NULL';
    echo "   ✓ {$buktiNama}\n";
    echo "     Verified: {$verified}, Tingkatan ID: {$tingkatan}\n";
}

// 5. Validasi semua verifikator penilaian is_verified
$verifNotVerified = \App\Models\Penilaian::where('source', 'esakip')
    ->where('role_id', $verifRole->id)
    ->where('is_verified', false)
    ->count();

echo "\n5. VALIDASI VERIFIKATOR PENILAIAN:\n";
if ($verifNotVerified > 0) {
    echo "   ✗ MASALAH: {$verifNotVerified} penilaian verifikator TIDAK ter-verified\n";
} else {
    echo "   ✓ Semua {$verifCount} penilaian verifikator ter-verified (is_verified = true)\n";
}

// 6. Breakdown per jenis dokumen
echo "\n6. BREAKDOWN PER JENIS DOKUMEN:\n";
$dokumenTypes = \App\Models\BuktiDukung::where('is_auto_verified', true)
    ->groupBy('esakip_document_type')
    ->select('esakip_document_type', \DB::raw('count(*) as total'))
    ->get();

foreach ($dokumenTypes as $doc) {
    $type = $doc->esakip_document_type;
    $total = $doc->total;

    $opdDokumen = \App\Models\Penilaian::where('source', 'esakip')
        ->where('role_id', $opdRole->id)
        ->whereHas('bukti_dukung', function ($q) use ($type) {
            $q->where('esakip_document_type', $type);
        })
        ->count();

    $verifDokumen = \App\Models\Penilaian::where('source', 'esakip')
        ->where('role_id', $verifRole->id)
        ->whereHas('bukti_dukung', function ($q) use ($type) {
            $q->where('esakip_document_type', $type);
        })
        ->count();

    echo "   - {$type}: {$total} bukti, {$opdDokumen} OPD penilaian, {$verifDokumen} verifikator penilaian\n";
}

// 7. Riwayat Sinkronisasi terakhir
echo "\n7. RIWAYAT SINKRONISASI TERAKHIR:\n";
$lastSync = \App\Models\RiwayatSinkron::orderBy('created_at', 'desc')->first();
if ($lastSync) {
    echo "   - Waktu: {$lastSync->created_at}\n";
    echo "   - Tahun: {$lastSync->tahun_id}\n";
    echo "   - OPD: {$lastSync->opd->nama}\n";
    echo "   - Dokumen: {$lastSync->document_type}\n";
    echo "   - Mode: {$lastSync->sync_mode}\n";
    echo "   - Status: {$lastSync->status}\n";
    echo "   - Berhasil: {$lastSync->success_count}\n";
    echo "   - Tidak Ditemukan: {$lastSync->not_found_count}\n";
    echo "   - Gagal: {$lastSync->failed_count}\n";
    echo "   - Dilewati: {$lastSync->skipped_count}\n";
}

echo "\n=== VALIDASI SELESAI ===\n";
