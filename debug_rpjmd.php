<?php
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rpjmdBukti = DB::table('bukti_dukung')
    ->where('nama', 'RPJMD')
    ->select('id', 'nama', 'esakip_document_type', 'tahun_id', 'is_n_minus_1')
    ->get();

echo "=== RPJMD BUKTI ===\n";
foreach ($rpjmdBukti as $b) {
    $tahunRow = DB::table('tahun')->where('id', $b->tahun_id)->first();
    echo "ID: {$b->id}, Tahun: {$tahunRow->tahun}, Type: {$b->esakip_document_type}, is_n_minus_1: {$b->is_n_minus_1}\n";
}

$allOpds = DB::table('opd')
    ->whereNotNull('esakip_opd_id')
    ->select('id', 'nama', 'esakip_opd_id')
    ->orderBy('id')
    ->get();

echo "\n=== ALL OPDs WITH esakip_opd_id ===\n";
echo "Total: " . count($allOpds) . "\n";
foreach ($allOpds as $o) {
    echo "ID: {$o->id}, Nama: {$o->nama}, ESAKIP ID: {$o->esakip_opd_id}\n";
}

echo "\n=== PENILAIAN FOR RPJMD ===\n";
$rpjmdPenilaian = DB::table('penilaian')
    ->join('bukti_dukung', 'penilaian.bukti_dukung_id', '=', 'bukti_dukung.id')
    ->where('bukti_dukung.nama', 'RPJMD')
    ->select('penilaian.id', 'penilaian.opd_id', 'penilaian.tahun_id', 'bukti_dukung.id as bukti_id')
    ->get();

echo "Total penilaian for RPJMD: " . count($rpjmdPenilaian) . "\n";
if (count($rpjmdPenilaian) > 0) {
    echo "OPD IDs with RPJMD penilaian:\n";
    $opdIds = [];
    foreach ($rpjmdPenilaian as $p) {
        echo "  Tahun: {$p->tahun_id}, OPD: {$p->opd_id}\n";
        $opdIds[] = $p->opd_id;
    }
    $opdIds = array_unique($opdIds);
    echo "Unique OPD Count with RPJMD: " . count($opdIds) . "\n";
} else {
    echo "NO PENILAIAN FOR RPJMD\n";
}
