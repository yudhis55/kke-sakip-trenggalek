<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING BUKTI DUKUNG KRITERIA KOMPONEN ===\n\n";

$buktiList = \App\Models\BuktiDukung::where('is_auto_verified', true)
    ->with('kriteriaKomponen.jenisNilai')
    ->get(['id', 'nama', 'kriteria_komponen_id', 'is_auto_verified']);

foreach ($buktiList as $bukti) {
    $hasKriteria = $bukti->kriteriaKomponen ? 'ADA' : 'NULL';
    $jenisNilaiId = $bukti->kriteriaKomponen?->jenis_nilai_id ?? 'NULL';

    echo "ID: {$bukti->id}, Nama: {$bukti->nama}\n";
    echo "  - kriteria_komponen_id: {$bukti->kriteria_komponen_id}\n";
    echo "  - KriteriaKomponen relation: {$hasKriteria}\n";
    echo "  - jenis_nilai_id: {$jenisNilaiId}\n\n";
}

// Check penilaian OPD
echo "\n=== CHECKING OPD PENILAIAN ===\n\n";
$opdRole = \App\Models\Role::where('jenis', 'opd')->first();
$opdPenilaian = \App\Models\Penilaian::where('source', 'esakip')
    ->where('role_id', $opdRole->id)
    ->with('buktiDukung')
    ->get(['id', 'bukti_dukung_id', 'tingkatan_nilai_id']);

foreach ($opdPenilaian->take(5) as $penilaian) {
    echo "Penilaian ID: {$penilaian->id}, Bukti: {$penilaian->buktiDukung->nama}, tingkatan_nilai_id: " . ($penilaian->tingkatan_nilai_id ?? 'NULL') . "\n";
}
