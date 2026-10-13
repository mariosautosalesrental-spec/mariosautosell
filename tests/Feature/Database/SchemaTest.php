<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, int> ids of one row per related table */
    private function relatedIds(): array
    {
        $now = now();

        return [
            'category_id' => DB::table('categories')->insertGetId(['name' => 'Jeep', 'created_at' => $now, 'updated_at' => $now]),
            'body_type_id' => DB::table('body_types')->insertGetId(['name' => 'SUV', 'slug' => 'suv', 'created_at' => $now, 'updated_at' => $now]),
            'transmission_id' => DB::table('transmissions')->insertGetId(['name' => 'Automatic', 'slug' => 'automatic', 'created_at' => $now, 'updated_at' => $now]),
            'drivetrain_id' => DB::table('drivetrains')->insertGetId(['name' => 'AWD', 'slug' => 'awd', 'created_at' => $now, 'updated_at' => $now]),
        ];
    }

    private function insertCar(array $overrides = []): int
    {
        return DB::table('cars')->insertGetId(array_merge($this->relatedIds(), [
            'title' => '2019 Jeep Compass',
            'slug' => 'car-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_all_tables_and_columns_exist(): void
    {
        $expected = [
            'body_types' => ['id', 'name', 'slug', 'sort_order', 'created_at', 'updated_at'],
            'transmissions' => ['id', 'name', 'slug', 'sort_order', 'created_at', 'updated_at'],
            'drivetrains' => ['id', 'name', 'slug', 'sort_order', 'created_at', 'updated_at'],
            'categories' => ['id', 'name', 'sort_order', 'created_at', 'updated_at'],
            'users' => ['id', 'name', 'email', 'password', 'remember_token', 'created_at', 'updated_at'],
            'settings' => ['id', 'whatsapp', 'phone', 'street', 'city', 'state', 'zip', 'hours_mon_fri', 'hours_sat', 'hours_sun', 'created_at', 'updated_at'],
            'cars' => ['id', 'category_id', 'body_type_id', 'transmission_id', 'drivetrain_id', 'title', 'trim', 'slug', 'price', 'mileage', 'color', 'engine', 'description', 'is_active', 'price_drop', 'sold_at', 'sold_price', 'created_at', 'updated_at'],
            'car_photos' => ['id', 'car_id', 'file_path', 'sort_order', 'created_at', 'updated_at'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
            $this->assertEqualsCanonicalizing($columns, Schema::getColumnListing($table), "Columns of {$table}");
        }
    }

    public function test_framework_tables_exist_but_not_job_tables(): void
    {
        foreach (['password_reset_tokens', 'sessions', 'cache'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $this->assertFalse(Schema::hasTable('jobs'));
        $this->assertFalse(Schema::hasTable('failed_jobs'));
    }

    public function test_lookup_tables_have_unique_name_and_slug(): void
    {
        foreach (['body_types', 'transmissions', 'drivetrains'] as $table) {
            $row = ['name' => 'X', 'slug' => 'x', 'created_at' => now(), 'updated_at' => now()];
            DB::table($table)->insert($row);

            $this->expectedUniqueViolation(fn () => DB::table($table)->insert(array_merge($row, ['slug' => 'y'])), "{$table}.name");
            $this->expectedUniqueViolation(fn () => DB::table($table)->insert(array_merge($row, ['name' => 'Y'])), "{$table}.slug");
        }
    }

    public function test_category_name_is_unique(): void
    {
        $row = ['name' => 'Jeep', 'created_at' => now(), 'updated_at' => now()];
        DB::table('categories')->insert($row);

        $this->expectedUniqueViolation(fn () => DB::table('categories')->insert($row), 'categories.name');
    }

    public function test_cars_defaults_is_active_true_and_price_drop_false(): void
    {
        $id = $this->insertCar();
        $car = DB::table('cars')->find($id);

        $this->assertEquals(1, $car->is_active);
        $this->assertEquals(0, $car->price_drop);
        $this->assertNull($car->price);
        $this->assertNull($car->sold_at);
        $this->assertNull($car->sold_price);
    }

    public function test_cars_slug_must_be_unique(): void
    {
        $this->insertCar(['slug' => 'same-slug']);

        $this->expectedUniqueViolation(fn () => $this->insertCar(['slug' => 'same-slug']), 'cars.slug');
    }

    public function test_category_with_cars_cannot_be_deleted_at_db_level(): void
    {
        $ids = $this->relatedIds();
        DB::table('cars')->insert(array_merge($ids, ['title' => 'Car', 'slug' => 'car', 'created_at' => now(), 'updated_at' => now()]));

        $this->expectException(QueryException::class);

        DB::table('categories')->where('id', $ids['category_id'])->delete();
    }

    public function test_lookup_rows_in_use_cannot_be_deleted(): void
    {
        $ids = $this->relatedIds();
        DB::table('cars')->insert(array_merge($ids, ['title' => 'Car', 'slug' => 'car', 'created_at' => now(), 'updated_at' => now()]));

        foreach (['body_types' => 'body_type_id', 'transmissions' => 'transmission_id', 'drivetrains' => 'drivetrain_id'] as $table => $fk) {
            try {
                DB::table($table)->where('id', $ids[$fk])->delete();
                $this->fail("{$table} row in use was deleted");
            } catch (QueryException) {
                $this->assertDatabaseHas($table, ['id' => $ids[$fk]]);
            }
        }
    }

    public function test_deleting_car_removes_its_photo_rows(): void
    {
        $carId = $this->insertCar();
        DB::table('car_photos')->insert([
            ['car_id' => $carId, 'file_path' => 'cars/1/a.jpg', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['car_id' => $carId, 'file_path' => 'cars/1/b.jpg', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('cars')->where('id', $carId)->delete();

        $this->assertSame(0, DB::table('car_photos')->where('car_id', $carId)->count());
    }

    public function test_cars_table_has_the_expected_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('cars'))->map(fn ($i) => $i['columns'])->all();

        $this->assertContains(['is_active', 'category_id'], $indexes);
        $this->assertContains(['is_active', 'created_at'], $indexes);
        $this->assertContains(['sold_at'], $indexes);
        $this->assertContains(['slug'], $indexes);
        $this->assertContains(['car_id', 'sort_order'], collect(Schema::getIndexes('car_photos'))->map(fn ($i) => $i['columns'])->all());
    }

    private function expectedUniqueViolation(callable $callback, string $what): void
    {
        try {
            $callback();
        } catch (QueryException) {
            $this->assertTrue(true);

            return;
        }

        $this->fail("Expected a unique violation on {$what}");
    }
}
