<?php

namespace Tests\Feature\Database;

use Database\Seeders\BodyTypeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DrivetrainSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TransmissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_seeders_create_expected_rows(): void
    {
        $this->seed([BodyTypeSeeder::class, TransmissionSeeder::class, DrivetrainSeeder::class]);

        $this->assertSame(['SUV', 'Car', 'Truck'], DB::table('body_types')->orderBy('sort_order')->pluck('name')->all());
        $this->assertSame(['suv', 'car', 'truck'], DB::table('body_types')->orderBy('sort_order')->pluck('slug')->all());
        $this->assertSame(['Automatic', 'Manual'], DB::table('transmissions')->orderBy('sort_order')->pluck('name')->all());
        $this->assertSame(['FWD', 'RWD', 'AWD', '4WD'], DB::table('drivetrains')->orderBy('sort_order')->pluck('name')->all());
        $this->assertSame(['fwd', 'rwd', 'awd', '4wd'], DB::table('drivetrains')->orderBy('sort_order')->pluck('slug')->all());
    }

    public function test_lookup_seeders_are_idempotent(): void
    {
        $this->seed([BodyTypeSeeder::class, TransmissionSeeder::class, DrivetrainSeeder::class]);
        $this->seed([BodyTypeSeeder::class, TransmissionSeeder::class, DrivetrainSeeder::class]);

        $this->assertSame(3, DB::table('body_types')->count());
        $this->assertSame(2, DB::table('transmissions')->count());
        $this->assertSame(4, DB::table('drivetrains')->count());
    }

    public function test_setting_seeder_creates_the_mockup_values(): void
    {
        $this->seed(SettingSeeder::class);

        $row = DB::table('settings')->first();

        $this->assertSame('19784174036', $row->whatsapp);
        $this->assertSame('(978) 417-4036', $row->phone);
        $this->assertSame('1336 Merrimack Ave', $row->street);
        $this->assertSame('Dracut', $row->city);
        $this->assertSame('MA', $row->state);
        $this->assertSame('01826', $row->zip);
        $this->assertSame('9:00 AM – 6:00 PM', $row->hours_mon_fri);
        $this->assertSame('9:00 AM – 4:00 PM', $row->hours_sat);
        $this->assertSame('Closed', $row->hours_sun);
    }

    public function test_setting_seeder_keeps_single_row(): void
    {
        $this->seed(SettingSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->assertSame(1, DB::table('settings')->count());
    }

    public function test_setting_seeder_does_not_overwrite_edited_values(): void
    {
        $this->seed(SettingSeeder::class);
        DB::table('settings')->update(['phone' => '(978) 000-0000']);

        $this->seed(SettingSeeder::class);

        $this->assertSame('(978) 000-0000', DB::table('settings')->value('phone'));
    }

    public function test_database_seeder_runs_without_demo_data_in_production(): void
    {
        $this->app['env'] = 'production';

        // Run the seeder directly: `db:seed` would ask for confirmation in production.
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->run();

        $this->assertSame(0, DB::table('cars')->count());
        $this->assertSame(0, DB::table('categories')->count());
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(1, DB::table('settings')->count());
        $this->assertSame(3, DB::table('body_types')->count());
    }
}
