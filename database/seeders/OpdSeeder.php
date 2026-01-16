<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapping OPD dengan esakip_opd_id dari E-SAKIP Trenggalek
        $data = [
            ['nama' => 'Dinas Pendidikan, Pemuda Dan Olahraga', 'esakip_opd_id' => '5'],
            ['nama' => 'Dinas Kesehatan, Pengendalian Penduduk Dan Keluarga Berencana', 'esakip_opd_id' => '6'],
            ['nama' => 'Dinas Pekerjaan Umum Dan Penataan Ruang', 'esakip_opd_id' => '7'],
            ['nama' => 'Dinas Perumahan, Kawasan Permukiman Dan Lingkungan Hidup', 'esakip_opd_id' => '8'],
            ['nama' => 'Satuan Polisi Pamong Praja Dan Kebakaran', 'esakip_opd_id' => '9'],
            ['nama' => 'Badan Kesatuan Bangsa Dan Politik', 'esakip_opd_id' => '26'],
            ['nama' => 'Badan Penanggulangan Bencana Daerah', 'esakip_opd_id' => '25'],
            ['nama' => 'Dinas Sosial, Pemberdayaan Perempuan Dan Perlindungan Anak', 'esakip_opd_id' => '10'],
            ['nama' => 'Dinas Perindustrian Dan Tenaga Kerja', 'esakip_opd_id' => '11'],
            ['nama' => 'Dinas Kependudukan Dan Pencatatan Sipil', 'esakip_opd_id' => '13'],
            ['nama' => 'Dinas Pemberdayaan Masyarakat Dan Desa', 'esakip_opd_id' => '14'],
            ['nama' => 'Dinas Perhubungan', 'esakip_opd_id' => '15'],
            ['nama' => 'Dinas Komunikasi Dan Informatika', 'esakip_opd_id' => '16'],
            ['nama' => 'Dinas Koperasi Dan Usaha Mikro Dan Perdagangan', 'esakip_opd_id' => '17'],
            ['nama' => 'Dinas Penanaman Modal Dan Pelayanan Terpadu Satu Pintu', 'esakip_opd_id' => '18'],
            ['nama' => 'Dinas Kearsipan Dan Perpustakaan', 'esakip_opd_id' => '19'],
            ['nama' => 'Dinas Perikanan', 'esakip_opd_id' => '20'],
            ['nama' => 'Dinas Pariwisata Dan Kebudayaan', 'esakip_opd_id' => '21'],
            ['nama' => 'Dinas Pertanian Dan Pangan', 'esakip_opd_id' => '12'],
            ['nama' => 'Dinas Peternakan', 'esakip_opd_id' => '42'],
            ['nama' => 'Sekretariat Daerah', 'esakip_opd_id' => '2'],
            ['nama' => 'Sekretariat DPRD', 'esakip_opd_id' => '3'],
            ['nama' => 'Kecamatan Trenggalek', 'esakip_opd_id' => '28'],
            ['nama' => 'Kecamatan Pogalan', 'esakip_opd_id' => '37'],
            ['nama' => 'Kecamatan Durenan', 'esakip_opd_id' => '39'],
            ['nama' => 'Kecamatan Watulimo', 'esakip_opd_id' => '32'],
            ['nama' => 'Kecamatan Munjungan', 'esakip_opd_id' => '34'],
            ['nama' => 'Kecamatan Kampak', 'esakip_opd_id' => '41'],
            ['nama' => 'Kecamatan Gandusari', 'esakip_opd_id' => '38'],
            ['nama' => 'Kecamatan Karangan', 'esakip_opd_id' => '31'],
            ['nama' => 'Kecamatan Suruh', 'esakip_opd_id' => '36'],
            ['nama' => 'Kecamatan Dongko', 'esakip_opd_id' => '40'],
            ['nama' => 'Kecamatan Pule', 'esakip_opd_id' => '35'],
            ['nama' => 'Kecamatan Panggul', 'esakip_opd_id' => '33'],
            ['nama' => 'Kecamatan Tugu', 'esakip_opd_id' => '30'],
            ['nama' => 'Kecamatan Bendungan', 'esakip_opd_id' => '29'],
            ['nama' => 'Inspektorat', 'esakip_opd_id' => '4'],
            ['nama' => 'Badan Perencanaan Pembangunan, Penelitian Dan Pengembangan Daerah', 'esakip_opd_id' => '22'],
            ['nama' => 'Badan Keuangan Daerah', 'esakip_opd_id' => '23'],
            ['nama' => 'Badan Kepegawaian Daerah', 'esakip_opd_id' => '24'],
        ];

        foreach ($data as $opd) {
            DB::table('opd')->insert($opd);
        }
    }
}
