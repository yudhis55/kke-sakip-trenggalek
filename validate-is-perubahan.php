<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VALIDASI DATABASE LINK_FILE ===\n\n";

// Ambil OPD Dinas Komunikasi
$opd = \App\Models\Opd::where('nama', 'like', '%Komunikasi%')->first();
echo "OPD: {$opd->nama} (ID: {$opd->id})\n\n";

// Ambil penilaian OPD dari esakip
$penilaianList = \App\Models\Penilaian::where('source', 'esakip')
    ->where('opd_id', $opd->id)
    ->whereNotNull('link_file')
    ->with('bukti_dukung')
    ->get();

echo "Total penilaian dengan link_file: {$penilaianList->count()}\n\n";

$totalInduk = 0;
$totalPerubahan = 0;

foreach ($penilaianList as $penilaian) {
    echo "─────────────────────────────────────\n";
    echo "Penilaian ID: {$penilaian->id}\n";
    echo "Bukti: {$penilaian->bukti_dukung->nama}\n";
    echo "Jenis Dokumen: {$penilaian->bukti_dukung->esakip_document_type}\n";

    if (is_array($penilaian->link_file) && count($penilaian->link_file) > 0) {
        echo "Jumlah files: " . count($penilaian->link_file) . "\n\n";

        foreach ($penilaian->link_file as $index => $file) {
            $fileName = $file['original_name'] ?? basename($file['url'] ?? '');
            $isPerubahan = $file['is_perubahan'] ?? null;

            echo "  File: {$fileName}\n";
            echo "  is_perubahan: ";

            if ($isPerubahan === null) {
                echo "NULL ❌\n";
            } elseif ($isPerubahan === true) {
                echo "TRUE (PERUBAHAN) ✅\n";
                $totalPerubahan++;
            } else {
                echo "FALSE (INDUK) ✅\n";
                $totalInduk++;
            }

            // Deteksi dari nama file
            if (stripos($fileName, 'Perubahan') !== false) {
                echo "  → Nama file mengandung 'Perubahan'\n";
            } elseif (stripos($fileName, 'Induk') !== false) {
                echo "  → Nama file mengandung 'Induk'\n";
            }

            echo "\n";
        }
    }
}

echo "─────────────────────────────────────\n";
echo "SUMMARY:\n";
echo "  ✓ Dokumen INDUK (is_perubahan=false): {$totalInduk}\n";
echo "  ✓ Dokumen PERUBAHAN (is_perubahan=true): {$totalPerubahan}\n";
echo "  Total: " . ($totalInduk + $totalPerubahan) . "\n\n";

if ($totalPerubahan == 0) {
    echo "ℹ️  Tidak ada dokumen perubahan untuk OPD ini.\n";
    echo "   Ini NORMAL jika semua dokumen adalah versi terbaru (induk).\n";
} else {
    echo "✅ Ada dokumen perubahan yang terdeteksi!\n";
}
