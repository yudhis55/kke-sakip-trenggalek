<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Penilaian;
use App\Models\Opd;
use App\Models\Role;
use App\Services\EsakipSyncService;
use Illuminate\Support\Facades\Log;

echo "=== TEST CASE 2: SKIP MANUAL UPLOAD ===\n\n";

// Get OPD role
$opdRole = Role::where('jenis', 'opd')->first();
if (!$opdRole) {
    echo "❌ Role OPD tidak ditemukan\n";
    exit(1);
}

// 1. Cari penilaian E-SAKIP yang ada
$penilaianEsakip = Penilaian::where('source', 'esakip')
    ->whereNotNull('link_file')
    ->where('role_id', $opdRole->id)
    ->first();

if (!$penilaianEsakip) {
    echo "❌ Tidak ada penilaian E-SAKIP untuk ditest\n";
    exit(1);
}

echo "1. Penilaian dipilih untuk test:\n";
echo "   ID: {$penilaianEsakip->id}\n";
echo "   Bukti: {$penilaianEsakip->bukti_dukung->nama}\n";
echo "   OPD: {$penilaianEsakip->opd->nama}\n";
echo "   Source: {$penilaianEsakip->source}\n";
$fileCount = count($penilaianEsakip->link_file ?? []);
echo "   Files: {$fileCount}\n\n";

// Backup data original
$backupFiles = $penilaianEsakip->link_file;
$backupSource = $penilaianEsakip->source;

// 2. Ubah source jadi 'upload' untuk simulasi manual upload
echo "2. Mengubah source menjadi 'upload' (simulasi manual upload)...\n";
$penilaianEsakip->update([
    'source' => 'upload',
    'keterangan' => 'Upload Manual Test - Jangan ditimpa!'
]);

echo "   ✓ Source diubah menjadi 'upload'\n";
echo "   ✓ Keterangan ditambahkan\n\n";

// 3. Jalankan sync untuk OPD yang sama
echo "3. Menjalankan sinkronisasi ulang untuk OPD yang sama...\n";
$esakipService = app(EsakipSyncService::class);

try {
    $result = $esakipService->processSync(
        $penilaianEsakip->bukti_dukung->tahun_id,
        $penilaianEsakip->opd_id,
        null, // semua dokumen
        function ($current, $total, $message) {
            echo "   Progress: {$current}/{$total} - {$message}\n";
        }
    );

    echo "\n   ✓ Sync selesai!\n";
    echo "   Results:\n";
    echo "   - Success: {$result['success_count']}\n";
    echo "   - Skipped: {$result['skipped_count']}\n";
    echo "   - Failed: {$result['failed_count']}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n";

    // Rollback
    $penilaianEsakip->update([
        'source' => $backupSource,
        'link_file' => $backupFiles
    ]);
    exit(1);
}

// 4. Validasi bahwa penilaian tidak berubah
echo "4. Validasi data setelah sync...\n";
$penilaianAfter = Penilaian::find($penilaianEsakip->id);

$sourceMatch = $penilaianAfter->source === 'upload';
$filesMatch = count($penilaianAfter->link_file ?? []) === $fileCount;
$keteranganMatch = $penilaianAfter->keterangan === 'Upload Manual Test - Jangan ditimpa!';

echo "   Source: {$penilaianAfter->source} " . ($sourceMatch ? '✓' : '❌') . "\n";
echo "   Files count: " . count($penilaianAfter->link_file ?? []) . " " . ($filesMatch ? '✓' : '❌') . "\n";
echo "   Keterangan preserved: " . ($keteranganMatch ? '✓' : '❌') . "\n\n";

// 5. Hasil
if ($sourceMatch && $filesMatch && $keteranganMatch) {
    echo "✅ TEST PASSED!\n";
    echo "   Data manual upload berhasil di-skip (tidak ditimpa)\n\n";

    // Rollback ke kondisi awal
    echo "5. Rollback ke kondisi E-SAKIP...\n";
    $penilaianAfter->update([
        'source' => 'esakip',
        'keterangan' => null
    ]);
    echo "   ✓ Penilaian dikembalikan ke source 'esakip'\n\n";

    echo "=== TEST COMPLETED SUCCESSFULLY ===\n";
    exit(0);
} else {
    echo "❌ TEST FAILED!\n";
    echo "   Data manual upload tidak di-skip dengan benar\n\n";

    // Rollback
    $penilaianAfter->update([
        'source' => $backupSource,
        'link_file' => $backupFiles,
        'keterangan' => null
    ]);

    exit(1);
}
