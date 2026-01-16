<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('setting')->insert([
            'buka_penilaian_mandiri'       => '2026-01-01 00:00:00',
            'tutup_penilaian_mandiri'      => '2026-12-31 23:59:59',
            'buka_penilaian_verifikator'   => '2026-01-01 00:00:00',
            'tutup_penilaian_verifikator'  => '2026-12-31 23:59:59',
            'buka_penilaian_penjamin'      => '2026-01-01 00:00:00',
            'tutup_penilaian_penjamin'     => '2026-12-31 23:59:59',
            'buka_penilaian_penilai'       => '2026-01-01 00:00:00',
            'tutup_penilaian_penilai'      => '2026-12-31 23:59:59',
            'tahun_id'                     => 2,   // asumsi tahun 2026 id=2
        ]);
    }
}
