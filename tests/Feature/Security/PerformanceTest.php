<?php

namespace Tests\Feature\Security;

use App\Models\Car;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> the SQL run while serving $uri */
    private function queries(string $uri): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($uri)->assertOk();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        return $queries;
    }

    public function test_public_pages_do_not_query_settings_once_the_cache_is_warm(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->create();

        foreach (['/', '/privacy', '/terms', '/accessibility', '/cars/'.$car->fresh()->slug] as $uri) {
            $this->get($uri); // warm
            $settingsQueries = array_filter($this->queries($uri), fn (string $sql) => str_contains($sql, '"settings"'));

            $this->assertSame([], array_values($settingsQueries), "{$uri} reads the settings table on every request");
        }
    }

    public function test_the_car_page_runs_a_small_fixed_number_of_queries(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->withPhotos(5)->create();
        $this->get('/cars/'.$car->fresh()->slug); // warm

        $this->assertLessThanOrEqual(8, count($this->queries('/cars/'.$car->fresh()->slug)));
    }

    public function test_the_sitemap_is_a_single_query_for_the_cars(): void
    {
        Car::factory()->count(20)->create();

        $carQueries = array_filter($this->queries('/sitemap.xml'), fn (string $sql) => str_contains($sql, 'from "cars"'));

        $this->assertCount(1, $carQueries);
    }

    public function test_the_indexes_used_by_the_public_listing_exist(): void
    {
        $indexes = collect(Schema::getIndexes('cars'))->pluck('columns')->all();

        $this->assertContains(['is_active', 'created_at'], $indexes);
        $this->assertContains(['is_active', 'category_id'], $indexes);
        $this->assertContains(['sold_at'], $indexes);
    }
}
