<?php

namespace Tests\Feature\Models;

use App\Exceptions\CategoryInUseException;
use App\Models\Car;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_active_cars_counts_only_active_cars(): void
    {
        $jeep = Category::factory()->create(['name' => 'Jeep']);
        Car::factory()->count(2)->for($jeep)->create();
        Car::factory()->for($jeep)->inactive()->create();
        Car::factory()->for($jeep)->sold()->create();

        $result = Category::withActiveCars()->get();

        $this->assertCount(1, $result);
        $this->assertSame(2, $result->first()->active_cars_count);
    }

    public function test_category_without_active_cars_is_excluded_from_public_scope(): void
    {
        $empty = Category::factory()->create();
        $onlyHidden = Category::factory()->create();
        $onlySold = Category::factory()->create();
        $live = Category::factory()->create();
        Car::factory()->for($onlyHidden)->inactive()->create();
        Car::factory()->for($onlySold)->sold()->create();
        Car::factory()->for($live)->create();

        $ids = Category::withActiveCars()->pluck('id')->all();

        $this->assertSame([$live->id], $ids);
        $this->assertNotContains($empty->id, $ids);
    }

    public function test_ordered_scope_follows_sort_order(): void
    {
        $third = Category::factory()->create(['sort_order' => 3]);
        $first = Category::factory()->create(['sort_order' => 1]);
        $second = Category::factory()->create(['sort_order' => 2]);

        $this->assertSame([$first->id, $second->id, $third->id], Category::ordered()->pluck('id')->all());
    }

    public function test_category_with_cars_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Car::factory()->for($category)->inactive()->create();

        $this->expectException(CategoryInUseException::class);

        $category->delete();
    }

    public function test_sold_car_still_blocks_category_deletion(): void
    {
        $category = Category::factory()->create();
        Car::factory()->for($category)->sold()->create();

        try {
            $category->delete();
            $this->fail('The category was deleted');
        } catch (CategoryInUseException $e) {
            $this->assertStringContainsString('still has 1 car', $e->getMessage());
            $this->assertDatabaseHas('categories', ['id' => $category->id]);
        }
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $category->delete();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_has_many_cars(): void
    {
        $category = Category::factory()->create();
        $cars = Car::factory()->count(2)->for($category)->create();

        $this->assertEqualsCanonicalizing($cars->pluck('id')->all(), $category->cars->pluck('id')->all());
    }
}
