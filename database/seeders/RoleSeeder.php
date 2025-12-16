<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['nama' => 'admin', 'jenis' => 'admin'],
            ['nama' => 'verifikator_bappeda', 'jenis' => 'verifikator'],
            ['nama' => 'verifikator_bag_organisasi', 'jenis' => 'verifikator'],
            ['nama' => 'verifikator_inspektorat', 'jenis' => 'verifikator'],
            ['nama' => 'penjamin', 'jenis' => 'penjamin'],
            ['nama' => 'penilai', 'jenis' => 'penilai'],
            ['nama' => 'opd', 'jenis' => 'opd'],
        ];

        foreach ($roles as $role) {
            DB::table('role')->insert($role);
        }
    }
}
