<?php

namespace Tests\Feature\Database;

use App\Models\Car;
use Database\Seeders\DemoCarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoCarSeederTest extends TestCase
{
    use RefreshDatabase;

    private function run_seeder(string $env): void
    {
        $this->app['env'] = $env;
        $this->app->make(DemoCarSeeder::class)->setContainer($this->app)->run();
    }

    public function test_demo_seeder_only_runs_locally(): void
    {
        Storage::fake('public');

        $this->run_seeder('production');

        $this->assertSame(0, Car::count());
    }

    public function test_demo_seeder_creates_active_hidden_and_sold_cars(): void
    {
        Storage::fake('public');

        $this->run_seeder('local');

        $this->assertGreaterThan(0, Car::active()->count());
        $this->assertGreaterThan(0, Car::hidden()->count());
        $this->assertGreaterThan(0, Car::sold()->count());
        $this->assertGreaterThan(0, Car::where('price_drop', true)->count());
        Car::with('photos')->get()->each(fn (Car $car) => $this->assertGreaterThan(0, $car->photos->count()));
        Storage::disk('public')->assertExists(Car::first()->photos->first()->file_path);
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        Storage::fake('public');

        $this->run_seeder('local');
        $count = Car::count();
        $this->run_seeder('local');

        $this->assertSame($count, Car::count());
    }
}
