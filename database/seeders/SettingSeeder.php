<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Seeds the single store-info row from the approved mockup. It never
     * overwrites a row that already exists, so values edited in the admin stay.
     */
    public function run(): void
    {
        if (DB::table('settings')->exists()) {
            return;
        }

        DB::table('settings')->insert(array_merge(Setting::defaults(), [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}
