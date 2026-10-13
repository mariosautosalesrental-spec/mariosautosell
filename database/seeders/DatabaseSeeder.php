<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production seeds only the lookup tables and the store info. Categories
     * are created by the store in the admin; demo cars are seeded only in the local environment.
     */
    public function run(): void
    {
        $this->call([
            BodyTypeSeeder::class,
            TransmissionSeeder::class,
            DrivetrainSeeder::class,
            SettingSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([DemoCarSeeder::class, AdminUserSeeder::class]);
        }
    }
}
