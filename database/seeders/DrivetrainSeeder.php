<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DrivetrainSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = collect([
            ['FWD', 'fwd'],
            ['RWD', 'rwd'],
            ['AWD', 'awd'],
            ['4WD', '4wd'],
        ])->map(fn ($row, $i) => [
            'name' => $row[0],
            'slug' => $row[1],
            'sort_order' => $i + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('drivetrains')->upsert($rows, ['slug'], ['name', 'sort_order', 'updated_at']);
    }
}
