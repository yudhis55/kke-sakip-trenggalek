<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Penilaian;
use App\Models\KriteriaKomponen;
use App\Models\Role;
use App\Models\SubKomponen;

echo "=== DEBUG PENILAIAN & CACHE ===\n\n";

$opdId = 1;

// Check total penilaian
$totalPenilaian = Penilaian::where('opd_id', $opdId)->count();
echo "1. Total penilaian for OPD {$opdId}: {$totalPenilaian}\n";

// Check by bukti_dukung_id
$penilaianKriteria = Penilaian::where('opd_id', $opdId)->whereNull('bukti_dukung_id')->count();
$penilaianBukti = Penilaian::where('opd_id', $opdId)->whereNotNull('bukti_dukung_id')->count();
echo "   - Penilaian di kriteria (bukti_dukung_id NULL): {$penilaianKriteria}\n";
echo "   - Penilaian di bukti (bukti_dukung_id NOT NULL): {$penilaianBukti}\n\n";

// Check SubKomponen penilaian mode
$penKriteria = SubKomponen::where('penilaian_di', 'kriteria')->count();
$penBukti = SubKomponen::where('penilaian_di', 'bukti')->count();
echo "2. SubKomponen configuration:\n";
echo "   - penilaian_di='kriteria': {$penKriteria}\n";
echo "   - penilaian_di='bukti': {$penBukti}\n\n";

// Get test data
$kriteriaIds = KriteriaKomponen::limit(10)->pluck('id')->toArray();
$roleIds = Role::whereIn('jenis', ['opd', 'penilai', 'penjamin'])->pluck('id')->toArray();

echo "3. Test parameters:\n";
echo "   - Kriteria IDs (first 10): " . implode(', ', $kriteriaIds) . "\n";
echo "   - Role IDs: " . implode(', ', $roleIds) . "\n\n";

// Try preload query
echo "4. Testing preload query:\n";
$penilaianList = Penilaian::whereIn('kriteria_komponen_id', $kriteriaIds)
    ->where('opd_id', $opdId)
    ->whereIn('role_id', $roleIds)
    ->whereNull('bukti_dukung_id')
    ->get();

echo "   - Query result: " . $penilaianList->count() . " records\n";

if ($penilaianList->count() > 0) {
    echo "   - Sample records:\n";
    foreach ($penilaianList->take(3) as $p) {
        echo "     * Kriteria: {$p->kriteria_komponen_id}, OPD: {$p->opd_id}, Role: {$p->role_id}\n";
    }
} else {
    echo "   - NO DATA FOUND!\n";
    echo "   - This explains why cache is empty\n";
}

echo "\n5. Checking actual penilaian patterns:\n";
$sample = Penilaian::where('opd_id', $opdId)->first();
if ($sample) {
    echo "   - Sample penilaian found:\n";
    echo "     * kriteria_komponen_id: {$sample->kriteria_komponen_id}\n";
    echo "     * bukti_dukung_id: " . ($sample->bukti_dukung_id ?? 'NULL') . "\n";
    echo "     * opd_id: {$sample->opd_id}\n";
    echo "     * role_id: {$sample->role_id}\n";
}
