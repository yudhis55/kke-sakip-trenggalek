<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VALIDASI STRUKTUR LINK_FILE ===\n\n";

// Ambil sample penilaian dari esakip
$penilaianList = \App\Models\Penilaian::where('source', 'esakip')
    ->with('bukti_dukung')
    ->take(10)
    ->get();

if ($penilaianList->count() == 0) {
    echo "❌ Tidak ada penilaian dari esakip. Silakan lakukan sinkronisasi terlebih dahulu.\n";
    exit;
}

echo "Found {$penilaianList->count()} penilaian from E-SAKIP\n\n";

$hasIsPerubahan = 0;
$missingIsPerubahan = 0;

foreach ($penilaianList as $penilaian) {
    echo "─────────────────────────────────────\n";
    echo "Penilaian ID: {$penilaian->id}\n";
    echo "Bukti: {$penilaian->bukti_dukung->nama}\n";
    echo "Source: {$penilaian->source}\n";

    if (is_array($penilaian->link_file) && count($penilaian->link_file) > 0) {
        echo "Jumlah files: " . count($penilaian->link_file) . "\n\n";

        foreach ($penilaian->link_file as $index => $file) {
            echo "  File #{$index}:\n";

            // Check required fields
            $hasUrl = isset($file['url']);
            $hasFromEsakip = isset($file['from_esakip']);
            $hasIsPerubahanField = isset($file['is_perubahan']);

            echo "    ✓ URL: " . ($hasUrl ? "✓" : "✗") . "\n";
            echo "    ✓ from_esakip: " . ($hasFromEsakip ? "✓" : "✗") . "\n";
            echo "    ✓ is_perubahan: " . ($hasIsPerubahanField ? "✓" : "✗");

            if ($hasIsPerubahanField) {
                $isPerubahan = $file['is_perubahan'];
                echo " (value: " . ($isPerubahan ? "TRUE - PERUBAHAN" : "FALSE - INDUK") . ")\n";
                $hasIsPerubahan++;
            } else {
                echo " ❌ MISSING!\n";
                $missingIsPerubahan++;
            }

            // Show full structure
            echo "    Structure: " . json_encode($file) . "\n";
        }
    } else {
        echo "❌ Tidak ada link_file\n";
    }
    echo "\n";
}

echo "─────────────────────────────────────\n";
echo "SUMMARY:\n";
echo "  ✓ Files dengan is_perubahan: {$hasIsPerubahan}\n";
echo "  ✗ Files tanpa is_perubahan: {$missingIsPerubahan}\n\n";

if ($missingIsPerubahan > 0) {
    echo "❌ MASIH ADA FILES YANG TIDAK PUNYA is_perubahan!\n";
    echo "Pastikan logika di EsakipSyncService.php sudah benar.\n";
} else {
    echo "✅ STRUKTUR LINK_FILE SUDAH SESUAI!\n";
    echo "Semua files memiliki field is_perubahan.\n";
}

