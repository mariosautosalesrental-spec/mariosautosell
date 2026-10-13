<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BodyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = collect([
            ['SUV', 'suv'],
            ['Car', 'car'],
            ['Truck', 'truck'],
        ])->map(fn ($row, $i) => [
            'name' => $row[0],
            'slug' => $row[1],
            'sort_order' => $i + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('body_types')->upsert($rows, ['slug'], ['name', 'sort_order', 'updated_at']);
    }
}
