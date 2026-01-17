<?php

/**
 * Script untuk testing sinkronisasi E-SAKIP
 * Jalankan: php test-sync.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Penilaian;
use App\Models\BuktiDukung;
use App\Services\EsakipSyncService;

echo "=== TESTING SINKRONISASI E-SAKIP ===\n\n";

// 1. Cek bukti dukung Renstra
echo "1. Checking Bukti Dukung Renstra...\n";
$buktiRenstra = BuktiDukung::with('kriteria_komponen')
    ->where('esakip_document_type', 'renstra')
    ->where('tahun_id', 1)
    ->get();

echo "   Found " . $buktiRenstra->count() . " Renstra bukti dukung\n";
echo "   Sample:\n";
foreach ($buktiRenstra->take(3) as $bd) {
    echo "   - ID: {$bd->id}, Nama: {$bd->nama}, Auto-Verify: " . ($bd->is_auto_verified ? 'YES' : 'NO');
    echo ", Penilaian Di: " . ($bd->kriteria_komponen?->penilaian_di ?? 'NULL');
    echo ", Role ID: {$bd->role_id}\n";
}
echo "\n";

// 2. Cek penilaian yang sudah ada dari sinkronisasi
echo "2. Checking existing Penilaian from E-SAKIP...\n";
$penilaianEsakip = Penilaian::where('source', 'esakip')
    ->with('role', 'opd', 'bukti_dukung')
    ->get();

echo "   Found " . $penilaianEsakip->count() . " penilaian from esakip\n";

// Grouping by role
$byRole = $penilaianEsakip->groupBy('role.jenis');
foreach ($byRole as $jenis => $items) {
    echo "   - Role {$jenis}: " . $items->count() . " penilaian\n";
}
echo "\n";

// 3. Cek penilaian OPD dengan tingkatan_nilai_id
echo "3. Checking OPD Penilaian with tingkatan_nilai_id...\n";
$penilaianOpdWithNilai = Penilaian::where('source', 'esakip')
    ->whereHas('role', function ($q) {
        $q->where('jenis', 'opd');
    })
    ->whereNotNull('tingkatan_nilai_id')
    ->with('tingkatan_nilai')
    ->get();

echo "   Found " . $penilaianOpdWithNilai->count() . " OPD penilaian with nilai\n";
if ($penilaianOpdWithNilai->isNotEmpty()) {
    echo "   Sample:\n";
    foreach ($penilaianOpdWithNilai->take(3) as $p) {
        echo "   - ID: {$p->id}, Bukti: {$p->bukti_dukung->nama}, Nilai: {$p->tingkatan_nilai->keterangan} (Bobot: {$p->tingkatan_nilai->bobot})\n";
    }
}
echo "\n";

// 4. Cek penilaian verifikator dengan is_verified
echo "4. Checking Verifikator Penilaian with is_verified...\n";
$penilaianVerif = Penilaian::where('source', 'esakip')
    ->whereHas('role', function ($q) {
        $q->where('jenis', 'verifikator');
    })
    ->where('is_verified', true)
    ->with('role', 'bukti_dukung')
    ->get();

echo "   Found " . $penilaianVerif->count() . " verifikator penilaian verified\n";
if ($penilaianVerif->isNotEmpty()) {
    echo "   Sample:\n";
    foreach ($penilaianVerif->take(3) as $p) {
        echo "   - ID: {$p->id}, Role: {$p->role->nama}, Bukti: {$p->bukti_dukung->nama}\n";
    }
}
echo "\n";

// 5. Cleanup option
echo "5. CLEANUP OPTION\n";
echo "   Do you want to delete all E-SAKIP penilaian? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if ($line === 'yes') {
    $deleted = Penilaian::where('source', 'esakip')->delete();
    echo "   ✓ Deleted {$deleted} penilaian\n\n";

    // Reset sync status
    $resetCount = BuktiDukung::where('sync_status', 'synced')->update([
        'sync_status' => 'not_synced',
        'last_synced_at' => null,
    ]);
    echo "   ✓ Reset {$resetCount} bukti_dukung sync status\n\n";

    echo "   Ready for re-sync! Go to UI and sync again.\n";
} else {
    echo "   Skipped cleanup.\n";
}

echo "\n=== TESTING COMPLETED ===\n";
