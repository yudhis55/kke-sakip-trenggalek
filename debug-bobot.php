<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG BOBOT ===\n\n";

// Test Komponen
$komponen = \App\Models\Komponen::first();
if ($komponen) {
    echo "KOMPONEN:\n";
    echo "Kode: {$komponen->kode}\n";
    echo "Nama: {$komponen->nama}\n";
    echo "Bobot (raw): {$komponen->bobot}\n";
    echo "Bobot Persen (accessor): {$komponen->bobot_persen}\n";
    echo "\n";
}

// Test SubKomponen
$subKomponen = \App\Models\SubKomponen::first();
if ($subKomponen) {
    echo "SUB KOMPONEN:\n";
    echo "Kode: {$subKomponen->kode}\n";
    echo "Nama: {$subKomponen->nama}\n";
    echo "Bobot (raw): {$subKomponen->bobot}\n";
    if (isset($subKomponen->bobot_persen)) {
        echo "Bobot Persen (accessor): {$subKomponen->bobot_persen}\n";
    } else {
        echo "Bobot Persen: TIDAK ADA ACCESSOR\n";
    }
    echo "\n";
}

// Test total bobot komponen
echo "TOTAL BOBOT KOMPONEN:\n";
$komponenList = \App\Models\Komponen::where('tahun_id', 1)->get();
$totalBobot = $komponenList->sum('bobot');
$totalBobotPersen = $komponenList->sum('bobot_persen');
echo "Total bobot (raw): {$totalBobot}\n";
echo "Total bobot_persen (accessor): {$totalBobotPersen}\n";
echo "Jumlah komponen: {$komponenList->count()}\n";
echo "\n";

// Test SubKomponen dari 1 komponen
if ($komponen) {
    echo "SUB KOMPONEN DARI KOMPONEN {$komponen->kode}:\n";
    $subKomponenList = $komponen->sub_komponen;
    $totalSubBobot = $subKomponenList->sum('bobot');
    echo "Total bobot sub komponen: {$totalSubBobot}\n";
    echo "Jumlah sub komponen: {$subKomponenList->count()}\n";
    foreach ($subKomponenList as $sub) {
        echo "  - {$sub->kode}: bobot = {$sub->bobot}\n";
    }
}
