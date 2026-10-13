<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A known admin login for local development only. In production the real
 * users are created with `php artisan make:filament-user`.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        User::firstOrCreate(
            ['email' => 'admin@marios.test'],
            ['name' => 'Admin', 'password' => 'password'],
        );
    }
}
