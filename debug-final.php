<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST PROGRESS DAN NILAI ===\n\n";

// Test OPD 13 yang punya penilaian
$opd = \App\Models\Opd::find(13);
echo "OPD: {$opd->nama}\n";
echo "Progress: " . $opd->getProgress(1) . "%\n";

// Hitung nilai total
$komponenList = \App\Models\Komponen::where('tahun_id', 1)->get();
$totalNilai = 0;
echo "\nKomponen:\n";
foreach ($komponenList as $komponen) {
    $nilai = $komponen->getNilaiRataRata($opd->id);
    $progress = $komponen->getProgress($opd->id);
    $totalNilai += $nilai;
    echo "  - {$komponen->kode}: bobot={$komponen->bobot}%, nilai={$nilai}, progress={$progress}%\n";
}
echo "\nTotal Nilai: {$totalNilai}\n";

// Test bobot total
$totalBobot = $komponenList->sum('bobot');
echo "Total Bobot Komponen: {$totalBobot}%\n";

// Test sub komponen dari AREN01
$komponen = \App\Models\Komponen::where('kode', 'AREN01')->first();
echo "\n\nSub Komponen dari {$komponen->kode}:\n";
$totalSubBobot = 0;
foreach ($komponen->sub_komponen as $sub) {
    $totalSubBobot += $sub->bobot;
    $nilai = $sub->getNilaiRataRata($opd->id);
    $progress = $sub->getProgress($opd->id);
    echo "  - {$sub->kode}: bobot={$sub->bobot}%, nilai={$nilai}, progress={$progress}%\n";
}
echo "Total Bobot Sub Komponen: {$totalSubBobot}%\n";
