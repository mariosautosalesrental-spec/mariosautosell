<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySortingTest extends TestCase
{
    use RefreshDatabase;

    private function names(): array
    {
        return Category::ordered()->pluck('name')->all();
    }

    public function test_created_without_order_goes_to_the_end(): void
    {
        Category::factory()->create(['name' => 'A', 'sort_order' => 5]);

        $new = Category::create(['name' => 'B']);

        $this->assertSame(6, $new->sort_order);
        $this->assertSame(['A', 'B'], $this->names());
    }

    public function test_explicit_sort_order_is_kept(): void
    {
        Category::factory()->create(['name' => 'A', 'sort_order' => 5]);

        $new = Category::create(['name' => 'B', 'sort_order' => 1]);

        $this->assertSame(1, $new->sort_order);
    }

    public function test_move_up_and_down_swap_neighbours(): void
    {
        $a = Category::create(['name' => 'A']);
        $b = Category::create(['name' => 'B']);
        $c = Category::create(['name' => 'C']);

        $c->moveUp();
        $this->assertSame(['A', 'C', 'B'], $this->names());

        $a->moveDown();
        $this->assertSame(['C', 'A', 'B'], $this->names());
    }

    public function test_first_cannot_move_up_and_last_cannot_move_down(): void
    {
        $a = Category::create(['name' => 'A']);
        $b = Category::create(['name' => 'B']);

        $a->moveUp();
        $b->moveDown();

        $this->assertSame(['A', 'B'], $this->names());
        $this->assertTrue($a->isFirst());
        $this->assertFalse($a->isLast());
        $this->assertTrue($b->isLast());
    }

    public function test_duplicated_and_gapped_sort_orders_are_renumbered_on_move(): void
    {
        $a = Category::factory()->create(['name' => 'A', 'sort_order' => 5]);
        $b = Category::factory()->create(['name' => 'B', 'sort_order' => 5]);
        $c = Category::factory()->create(['name' => 'C', 'sort_order' => 40]);

        $c->moveUp();

        $this->assertSame(['A', 'C', 'B'], $this->names());
        $this->assertSame([1, 2, 3], Category::ordered()->pluck('sort_order')->all());
    }

    public function test_single_category_is_both_first_and_last(): void
    {
        $only = Category::create(['name' => 'Only']);

        $this->assertTrue($only->isFirst());
        $this->assertTrue($only->isLast());
        $only->moveUp();
        $only->moveDown();
        $this->assertSame(['Only'], $this->names());
    }
}
