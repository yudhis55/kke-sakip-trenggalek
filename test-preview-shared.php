<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tahun;
use App\Models\Opd;
use App\Services\EsakipSyncService;
use Illuminate\Support\Facades\Log;

// Test parameters
$tahun = Tahun::where('tahun', 2025)->first();
$opd = Opd::where('id', 5)->first(); // Dinas Pendidikan

if (!$tahun) {
    echo "Tahun 2025 tidak ditemukan\n";
    exit(1);
}

if (!$opd) {
    echo "OPD ID 5 tidak ditemukan\n";
    exit(1);
}

echo "Testing Preview Sync with Shared Documents\n";
echo "==========================================\n";
echo "Tahun: {$tahun->tahun}\n";
echo "OPD: {$opd->nama}\n";
echo "Document Types: rpjmd\n";
echo "==========================================\n\n";

// Run preview
$service = app(EsakipSyncService::class);
$preview = $service->previewSync($tahun->id, $opd->id, ['rpjmd']);

// Print preview result
echo json_encode($preview, JSON_PRETTY_PRINT);
echo "\n\n";

// Check logs
echo "Recent logs:\n";
echo "==========================================\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50); // Last 50 lines
    echo implode('', $recentLines);
}
