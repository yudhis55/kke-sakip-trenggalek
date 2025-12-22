<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TahunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['tahun' => 2025, 'is_active' => true],
            ['tahun' => 2026, 'is_active' => false],
        ];

        DB::table('tahun')->insert($data);
    }
}
