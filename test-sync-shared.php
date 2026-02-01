<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tahun;
use App\Models\Opd;
use App\Services\EsakipSyncService;
use Illuminate\Support\Facades\Log;

// Test parameters - sync RPJMD untuk beberapa OPD
$tahun = Tahun::where('tahun', 2025)->first();
$testOpds = Opd::whereIn('id', [5, 6, 7])->whereNotNull('esakip_opd_id')->get(); // 3 OPD test

if (!$tahun) {
    echo "Tahun 2025 tidak ditemukan\n";
    exit(1);
}

if ($testOpds->isEmpty()) {
    echo "Test OPDs tidak ditemukan\n";
    exit(1);
}

echo "Testing Full Sync with Shared Documents\n";
echo "==========================================\n";
echo "Tahun: {$tahun->tahun}\n";
echo "Test OPDs:\n";
foreach ($testOpds as $opd) {
    echo "  - [{$opd->id}] {$opd->nama} (esakip_opd_id: {$opd->esakip_opd_id})\n";
}
echo "Document Types: rpjmd\n";
echo "==========================================\n\n";

$service = app(EsakipSyncService::class);

// Sync untuk OPD pertama saja sebagai test
$testOpd = $testOpds->first();
echo "\nSyncing OPD: {$testOpd->nama}\n";
echo "-------------------------------------------\n";

$result = $service->processSync($tahun->id, $testOpd->id, ['rpjmd']);

echo json_encode($result, JSON_PRETTY_PRINT);
echo "\n";

echo "\n==========================================\n";
echo "Sync completed!\n";
