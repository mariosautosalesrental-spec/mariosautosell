<?php

namespace Database\Seeders;

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

        DB::table('settings')->insert([
            'whatsapp' => '19784174036',
            'phone' => '(978) 417-4036',
            'street' => '1336 Merrimack Ave',
            'city' => 'Dracut',
            'state' => 'MA',
            'zip' => '01826',
            'hours_mon_fri' => '9:00 AM – 6:00 PM',
            'hours_sat' => '9:00 AM – 4:00 PM',
            'hours_sun' => 'Closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
