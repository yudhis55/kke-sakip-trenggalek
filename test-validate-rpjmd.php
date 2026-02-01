<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FileBuktiDukung;
use App\Models\Tahun;

$tahun = Tahun::where('tahun', 2025)->first();

echo "Verifikasi File RPJMD yang Disinkronkan\n";
echo "==========================================\n\n";

// Get files RPJMD dari esakip - query simple tanpa where has
$files = FileBuktiDukung::whereNotNull('esakip_file')
    ->where('esakip_file', 'like', '%rpjmd%')
    ->with('opd')
    ->limit(10)
    ->get();

echo "Total files RPJMD (sample 10): " . $files->count() . "\n\n";

foreach ($files as $file) {
    echo "File ID: {$file->id}\n";
    echo "Nama File: {$file->nama_file}\n";
    echo "E-SAKIP File: " . basename($file->esakip_file) . "\n";
    echo "OPD: {$file->opd->nama}\n";
    echo "Auto Verified: " . ($file->is_auto_verified ? 'YES' : 'NO') . "\n";
    echo "Page Number: {$file->page_number}\n";
    echo "-------------------------------------------\n";
}

// Count by OPD
echo "\nDistribusi RPJMD per OPD:\n";
echo "==========================================\n";
$allFiles = FileBuktiDukung::whereNotNull('esakip_file')
    ->where('esakip_file', 'like', '%rpjmd%')
    ->with('opd')
    ->get();

echo "Total RPJMD files: " . $allFiles->count() . "\n\n";

$opdDistribution = $allFiles->groupBy(function ($file) {
    return $file->opd->nama;
});

foreach ($opdDistribution as $opdName => $files) {
    echo "{$opdName}: {$files->count()} files\n";
}
