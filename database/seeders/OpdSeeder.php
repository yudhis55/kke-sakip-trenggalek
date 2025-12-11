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
        $data = [
            "Dinas Pendidikan, Pemuda Dan Olahraga",
            "Dinas Kesehatan, Pengendalian Penduduk Dan Keluarga Berencana",
            "Dinas Pekerjaan Umum Dan Penataan Ruang",
            "Dinas Perumahan, Kawasan Permukiman Dan Lingkungan Hidup",
            "Satuan Polisi Pamong Praja Dan Kebakaran",
            "Badan Kesatuan Bangsa Dan Politik",
            "Badan Penanggulangan Bencana Daerah",
            "Dinas Sosial, Pemberdayaan Perempuan Dan Perlindungan Anak",
            "Dinas Perindustrian Dan Tenaga Kerja",
            "Dinas Kependudukan Dan Pencatatan Sipil",
            "Dinas Pemberdayaan Masyarakat Dan Desa",
            "Dinas Perhubungan",
            "Dinas Komunikasi Dan Informatika",
            "Dinas Koperasi Dan Usaha Mikro Dan Perdagangan",
            "Dinas Penanaman Modal Dan Pelayanan Terpadu Satu Pintu",
            "Dinas Kearsipan Dan Perpustakaan",
            "Dinas Perikanan",
            "Dinas Pariwisata Dan Kebudayaan",
            "Dinas Pertanian Dan Pangan",
            "Dinas Peternakan",
            "Sekretariat Daerah",
            "Sekretariat DPRD",
            "Kecamatan Trenggalek",
            "Kecamatan Pogalan",
            "Kecamatan Durenan",
            "Kecamatan Watulimo",
            "Kecamatan Munjungan",
            "Kecamatan Kampak",
            "Kecamatan Gandusari",
            "Kecamatan Karangan",
            "Kecamatan Suruh",
            "Kecamatan Dongko",
            "Kecamatan Pule",
            "Kecamatan Panggul",
            "Kecamatan Tugu",
            "Kecamatan Bendungan",
            "Inspektorat",
            "Badan Perencanaan Pembangunan, Penelitian Dan Pengembangan Daerah",
            "Badan Keuangan Daerah",
            "Badan Kepegawaian Daerah",
        ];

        foreach ($data as $name) {
            DB::table('opd')->insert([
                'nama' => $name
            ]);
        }
    }
}
