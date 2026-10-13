<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production seeds only the lookup tables and the store info. Categories
     * are created by the store in the admin; demo data is local only.
     */
    public function run(): void
    {
        $this->call([
            BodyTypeSeeder::class,
            TransmissionSeeder::class,
            DrivetrainSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
