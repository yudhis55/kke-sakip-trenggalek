<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING is_perubahan LOGIC ===\n\n";

// Test dengan OPD yang punya dokumen perubahan (Dinas Koperasi dan Usaha Mikro - OPD ID 17)
echo "1. Testing Renstra dengan kategori 'induk' dan 'perubahan'\n";
echo "   OPD: Dinas Koperasi dan Usaha Mikro dan Perdagangan (ID: 17)\n\n";

// Fetch dari API untuk melihat struktur aslinya
$url = "https://e-sakip.trenggalekkab.go.id/api/dokumen/renstra";
$response = \Illuminate\Support\Facades\Http::connectTimeout(60)
    ->timeout(60)
    ->withoutVerifying()
    ->get($url, [
        'tahun' => 2025,
        'opd' => 17,
    ]);

if ($response->successful()) {
    $result = $response->json();
    $documents = $result['data']['Dinas Koperasi dan Usaha Mikro dan Perdagangan'] ?? [];

    echo "Found " . count($documents) . " documents:\n\n";

    foreach ($documents as $index => $doc) {
        $kategori = $doc['kategori'] ?? 'TIDAK ADA';
        $isPerubahan = isset($doc['kategori']) && $doc['kategori'] === 'perubahan';

        echo "Document #" . ($index + 1) . ":\n";
        echo "  Periode: {$doc['periode']}\n";
        echo "  Tanggal: {$doc['tanggal_publish']}\n";
        echo "  Kategori: {$kategori}\n";
        echo "  → is_perubahan: " . ($isPerubahan ? "TRUE" : "FALSE") . "\n";
        echo "  File: " . basename($doc['file']) . "\n\n";
    }
} else {
    echo "Failed to fetch from API\n";
}

echo "\n2. Testing IKU tanpa field kategori\n";
echo "   OPD: Dinas Koperasi dan Usaha Mikro dan Perdagangan (ID: 17)\n\n";

$url2 = "https://e-sakip.trenggalekkab.go.id/api/dokumen/iku";
$response2 = \Illuminate\Support\Facades\Http::connectTimeout(60)
    ->timeout(60)
    ->withoutVerifying()
    ->get($url2, [
        'tahun' => 2025,
        'opd' => 17,
    ]);

if ($response2->successful()) {
    $result2 = $response2->json();
    $documents2 = $result2['data']['Dinas Koperasi dan Usaha Mikro dan Perdagangan'] ?? [];

    echo "Found " . count($documents2) . " documents:\n\n";

    foreach ($documents2 as $index => $doc) {
        $kategori = $doc['kategori'] ?? 'TIDAK ADA';
        $isPerubahan = isset($doc['kategori']) && $doc['kategori'] === 'perubahan';

        echo "Document #" . ($index + 1) . ":\n";
        echo "  Periode: {$doc['periode']}\n";
        echo "  Tanggal: {$doc['tanggal_publish']}\n";
        echo "  Kategori: {$kategori}\n";
        echo "  → is_perubahan: " . ($isPerubahan ? "TRUE" : "FALSE") . "\n";
        echo "  Keterangan: " . ($doc['keterangan'] ?? 'N/A') . "\n\n";
    }
} else {
    echo "Failed to fetch from API\n";
}

echo "\n=== TESTING COMPLETED ===\n";
