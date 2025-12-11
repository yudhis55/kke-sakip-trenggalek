<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisNilaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('jenis_nilai')->insert([
            [ 'nama' => 'A/B/C/D' ],
            [ 'nama' => 'Y/T' ],
        ]);
    }
}
