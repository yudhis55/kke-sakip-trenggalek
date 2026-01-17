<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VALIDASI MULTIPLE DOKUMEN PER PENILAIAN ===\n\n";

// Ambil penilaian OPD dari esakip
$penilaianList = \App\Models\Penilaian::where('source', 'esakip')
    ->whereNotNull('link_file')
    ->with('bukti_dukung')
    ->get();

if ($penilaianList->count() == 0) {
    echo "❌ Tidak ada penilaian dari esakip. Silakan lakukan sinkronisasi terlebih dahulu.\n";
    exit;
}

echo "Found {$penilaianList->count()} penilaian from E-SAKIP\n\n";

$totalPenilaian = 0;
$penilaianWith1File = 0;
$penilaianWithMultipleFiles = 0;
$totalFiles = 0;

$groupedByBukti = [];

foreach ($penilaianList as $penilaian) {
    $totalPenilaian++;
    $buktiNama = $penilaian->bukti_dukung->nama;
    $buktiType = $penilaian->bukti_dukung->esakip_document_type;

    if (!isset($groupedByBukti[$buktiType])) {
        $groupedByBukti[$buktiType] = [
            'penilaian_count' => 0,
            'total_files' => 0,
            'samples' => []
        ];
    }

    $fileCount = is_array($penilaian->link_file) ? count($penilaian->link_file) : 0;
    $totalFiles += $fileCount;

    if ($fileCount == 1) {
        $penilaianWith1File++;
    } elseif ($fileCount > 1) {
        $penilaianWithMultipleFiles++;
    }

    $groupedByBukti[$buktiType]['penilaian_count']++;
    $groupedByBukti[$buktiType]['total_files'] += $fileCount;

    if (count($groupedByBukti[$buktiType]['samples']) < 2) {
        $groupedByBukti[$buktiType]['samples'][] = [
            'id' => $penilaian->id,
            'bukti_nama' => $buktiNama,
            'file_count' => $fileCount,
            'files' => $penilaian->link_file
        ];
    }
}

echo "─────────────────────────────────────\n";
echo "SUMMARY:\n";
echo "  Total Penilaian: {$totalPenilaian}\n";
echo "  Total Files: {$totalFiles}\n";
echo "  Penilaian dengan 1 file: {$penilaianWith1File}\n";
echo "  Penilaian dengan multiple files: {$penilaianWithMultipleFiles}\n";
echo "  Rata-rata files per penilaian: " . round($totalFiles / max($totalPenilaian, 1), 2) . "\n\n";

echo "─────────────────────────────────────\n";
echo "BREAKDOWN PER JENIS DOKUMEN:\n\n";

foreach ($groupedByBukti as $type => $data) {
    echo "📁 {$type}:\n";
    echo "   Penilaian: {$data['penilaian_count']}\n";
    echo "   Total Files: {$data['total_files']}\n";
    echo "   Avg Files/Penilaian: " . round($data['total_files'] / max($data['penilaian_count'], 1), 2) . "\n";

    if (count($data['samples']) > 0) {
        echo "   Samples:\n";
        foreach ($data['samples'] as $sample) {
            echo "     - Penilaian #{$sample['id']} ({$sample['bukti_nama']}): {$sample['file_count']} files\n";

            if ($sample['file_count'] > 1) {
                foreach ($sample['files'] as $idx => $file) {
                    $fileName = $file['original_name'] ?? 'N/A';
                    $isPerubahan = isset($file['is_perubahan']) ? ($file['is_perubahan'] ? 'PERUBAHAN' : 'INDUK') : 'N/A';
                    echo "       " . ($idx + 1) . ". {$fileName} ({$isPerubahan})\n";
                }
            }
        }
    }
    echo "\n";
}

echo "─────────────────────────────────────\n";

if ($penilaianWithMultipleFiles > 0) {
    echo "✅ ADA PENILAIAN DENGAN MULTIPLE FILES!\n";
    echo "Logika append sudah bekerja dengan benar.\n";
} else {
    echo "⚠️  TIDAK ADA PENILAIAN DENGAN MULTIPLE FILES\n";
    echo "Semua penilaian hanya memiliki 1 file.\n";
    echo "Kemungkinan:\n";
    echo "  1. API hanya mengirim 1 dokumen per OPD\n";
    echo "  2. Logika append belum bekerja\n";
}
