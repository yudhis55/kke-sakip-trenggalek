<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Administrator',
            'email' => 'admin@sakip.com',
            'password' => Hash::make('password'),
            'role_id' => 1,   // admin
            'opd_id' => null,
        ]);

        // User Verifikator
        DB::table('users')->insert([
            'name' => 'Verifikator Bappeda',
            'email' => 'verifikator@bappeda.com',
            'password' => Hash::make('password'),
            'role_id' => 2,   // verifikator
            'opd_id' => null,
        ]);

        DB::table('users')->insert([
            'name' => 'Verifikator Bag Organisasi',
            'email' => 'verifikator@organisasi.com',
            'password' => Hash::make('password'),
            'role_id' => 3,   // verifikator
            'opd_id' => null,
        ]);

        DB::table('users')->insert([
            'name' => 'Verifikator Inspektorat',
            'email' => 'verifikator@inspektorat.com',
            'password' => Hash::make('password'),
            'role_id' => 4,   // verifikator
            'opd_id' => null,
        ]);

        DB::table('users')->insert([
            'name' => 'Evaluator',
            'email' => 'evaluator@inspektorat.com',
            'password' => Hash::make(value: 'password'),
            'role_id' => 5,   // penjamin
            'opd_id' => null,
        ]);

        DB::table('users')->insert([
            'name' => 'Penjamin Kualitas',
            'email' => 'penjamin@inspektorat.com',
            'password' => Hash::make('password'),
            'role_id' => 6,   // penilai
            'opd_id' => null,
        ]);

        // Generate User untuk tiap OPD
         $singkatan = [
            "Disdikpora",
            "Dinkes",
            "DPUPR",
            "Disperkim",
            "Satpolpp",
            "Kesbangpol",
            "Bpbd",
            "Dinsos",
            "Disperinaker",
            "Disdukcapil",
            "Dpmd",
            "Dishub",
            "Diskominfo",
            "Diskopdag",
            "Dpmptsp",
            "Disarpus",
            "Diskan",
            "Disparbud",
            "Distanpan",
            "Disnak",
            "Setda",
            "Setwan",
            "Trenggalek",
            "Pogalan",
            "Durenan",
            "Watulimo",
            "Munjungan",
            "Kampak",
            "Gandusari",
            "Karangan",
            "Suruh",
            "Dongko",
            "Pule",
            "Panggul",
            "Tugu",
            "Bendungan",
            "Inspektorat",
            "Bappedalitbang",
            "Bakeuda",
            "Bkd"
        ];

        $opds = DB::table('opd')->get();

        $i = 0;
        foreach ($opds as $opd) {
            $username = strtolower($singkatan[$i]);

            DB::table('users')->insert([
                'name' => $opd->nama,
                'email' => $username . '@opd.com',
                'password' => Hash::make('password'),
                'role_id' => 7,       // role OPD
                'opd_id' => $opd->id  // sesuai OPD
            ]);

            $i++;
        }
    }
}
