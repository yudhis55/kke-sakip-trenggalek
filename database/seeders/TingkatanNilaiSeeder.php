<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TingkatanNilaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil ID jenis nilai
        $jenis = DB::table('jenis_nilai')->pluck('id', 'nama')->toArray();

        DB::table('tingkatan_nilai')->insert([
            // Untuk jenis A/B/C/D
            [
                'kode_nilai' => 'A',
                'bobot' => 1,
                'jenis_nilai_id' => $jenis['A/B/C/D']
            ],
            [
                'kode_nilai' => 'B',
                'bobot' => 0.66,
                'jenis_nilai_id' => $jenis['A/B/C/D']
            ],
            [
                'kode_nilai' => 'C',
                'bobot' => 0.33,
                'jenis_nilai_id' => $jenis['A/B/C/D']
            ],
            [
                'kode_nilai' => 'D',
                'bobot' => 0,
                'jenis_nilai_id' => $jenis['A/B/C/D']
            ],

            // Untuk jenis Y/T
            [
                'kode_nilai' => 'Y',
                'bobot' => 1,
                'jenis_nilai_id' => $jenis['Y/T']
            ],
            [
                'kode_nilai' => 'T',
                'bobot' => 0,
                'jenis_nilai_id' => $jenis['Y/T']
            ],
        ]);
    }
}
