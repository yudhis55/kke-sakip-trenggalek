<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            RoleSeeder::class,
            TahunSeeder::class,
            SettingSeeder::class,
            OpdSeeder::class,
            UserSeeder::class,
            JenisNilaiSeeder::class,
            TingkatanNilaiSeeder::class,
            MappingSeeder::class,
        ]);
    }
}
