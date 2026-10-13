<?php

namespace Tests\Feature\Site;

use App\Livewire\Inventory;
use App\Models\BodyType;
use App\Models\Car;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function bodyType(string $name): BodyType
    {
        return BodyType::factory()->create(['name' => $name, 'slug' => strtolower($name)]);
    }

    private function category(string $name, int $order = 1): Category
    {
        return Category::factory()->create(['name' => $name, 'sort_order' => $order]);
    }

    // ----------------------------------------------------------------- listing

    public function test_only_active_cars_are_listed(): void
    {
        $active = Car::factory()->create(['title' => '2019 Active Car']);
        Car::factory()->inactive()->create(['title' => '2019 Hidden Car']);
        Car::factory()->sold()->create(['title' => '2019 Sold Car']);

        Livewire::test(Inventory::class)
            ->assertSee('2019 Active Car')
            ->assertDontSee('2019 Hidden Car')
            ->assertDontSee('2019 Sold Car')
            ->assertViewHas('total', 1)
            ->assertViewHas('cars', fn ($cars) => $cars->pluck('id')->all() === [$active->id]);
    }

    public function test_orders_by_created_at_desc(): void
    {
        $old = Car::factory()->create(['created_at' => now()->subDays(3)]);
        $new = Car::factory()->create(['created_at' => now()->subDay()]);
        $middle = Car::factory()->create(['created_at' => now()->subDays(2)]);

        Livewire::test(Inventory::class)
            ->assertViewHas('cars', fn ($cars) => $cars->pluck('id')->all() === [$new->id, $middle->id, $old->id]);
    }

    // ----------------------------------------------------------------- filters

    public function test_filters_by_make_body_and_max_price_combined(): void
    {
        $jeep = $this->category('Jeep');
        $fiat = $this->category('Fiat', 2);
        $suv = $this->bodyType('SUV');
        $truck = $this->bodyType('Truck');

        $match = Car::factory()->for($jeep)->create(['body_type_id' => $suv->id, 'price' => 14000]);
        Car::factory()->for($jeep)->create(['body_type_id' => $suv->id, 'price' => 18000]);   // too expensive
        Car::factory()->for($jeep)->create(['body_type_id' => $truck->id, 'price' => 14000]); // wrong body
        Car::factory()->for($fiat)->create(['body_type_id' => $suv->id, 'price' => 14000]);   // wrong make

        Livewire::test(Inventory::class)
            ->set('make', 'jeep')
            ->set('body', 'suv')
            ->set('max', '15000')
            ->assertViewHas('total', 1)
            ->assertViewHas('cars', fn ($cars) => $cars->pluck('id')->all() === [$match->id]);
    }

    public function test_max_price_is_inclusive(): void
    {
        Car::factory()->create(['price' => 15000]);
        Car::factory()->create(['price' => 15001]);

        Livewire::test(Inventory::class)
            ->set('max', '15000')
            ->assertViewHas('total', 1);
    }

    public function test_make_filter_uses_the_slug_of_the_category_name(): void
    {
        $vw = $this->category('Volkswagen');
        Car::factory()->for($vw)->create();
        Car::factory()->create();

        Livewire::test(Inventory::class)
            ->set('make', 'volkswagen')
            ->assertViewHas('total', 1);
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        Car::factory()->count(2)->create();

        Livewire::test(Inventory::class)
            ->set('make', 'does-not-exist')
            ->set('body', 'spaceship')
            ->set('max', 'abc')
            ->set('page', '-5')
            ->assertOk()
            ->assertViewHas('total', 2);
    }

    public function test_make_of_a_category_without_active_cars_is_ignored(): void
    {
        $empty = $this->category('Ghost');
        Car::factory()->for($empty)->inactive()->create();
        Car::factory()->count(2)->create();

        Livewire::test(Inventory::class)
            ->set('make', 'ghost')
            ->assertViewHas('total', 2);
    }

    public function test_zero_or_negative_max_is_ignored(): void
    {
        Car::factory()->count(2)->create();

        Livewire::test(Inventory::class)->set('max', '0')->assertViewHas('total', 2);
        Livewire::test(Inventory::class)->set('max', '-100')->assertViewHas('total', 2);
    }

    // -------------------------------------------------------------- pagination

    public function test_shows_12_then_24_after_show_more(): void
    {
        Car::factory()->count(30)->create();

        Livewire::test(Inventory::class)
            ->assertViewHas('shown', 12)
            ->call('showMore')
            ->assertViewHas('shown', 24)
            ->call('showMore')
            ->assertViewHas('shown', 30);
    }

    public function test_counter_matches_filtered_total(): void
    {
        $jeep = $this->category('Jeep');
        Car::factory()->count(14)->for($jeep)->create();
        Car::factory()->count(3)->create();

        Livewire::test(Inventory::class)
            ->assertSee('Showing 12 of 17 cars')
            ->set('make', 'jeep')
            ->assertSee('Showing 12 of 14 cars')
            ->assertSee('14 cars');
    }

    public function test_show_more_button_hidden_at_end(): void
    {
        Car::factory()->count(13)->create();

        Livewire::test(Inventory::class)
            ->assertSee('Show more cars')
            ->call('showMore')
            ->assertDontSee('Show more cars')
            ->assertSee('Showing 13 of 13 cars');
    }

    public function test_show_more_button_absent_when_everything_fits(): void
    {
        Car::factory()->count(5)->create();

        Livewire::test(Inventory::class)->assertDontSee('Show more cars')->assertSee('Showing 5 of 5 cars');
    }

    public function test_changing_any_filter_resets_to_first_12(): void
    {
        $jeep = $this->category('Jeep');
        $suv = $this->bodyType('SUV');
        Car::factory()->count(30)->for($jeep)->create(['body_type_id' => $suv->id]);

        Livewire::test(Inventory::class)
            ->call('showMore')->assertViewHas('shown', 24)
            ->set('make', 'jeep')->assertViewHas('shown', 12)
            ->call('showMore')->assertViewHas('shown', 24)
            ->set('body', 'suv')->assertViewHas('shown', 12)
            ->call('showMore')->assertViewHas('shown', 24)
            ->set('max', '99999')->assertViewHas('shown', 12)
            ->call('showMore')->assertViewHas('shown', 24)
            ->call('selectMake', '')->assertViewHas('shown', 12);
    }

    public function test_cars_per_page_comes_from_config(): void
    {
        config(['marios.cars_per_page' => 5]);
        Car::factory()->count(8)->create();

        Livewire::test(Inventory::class)->assertViewHas('shown', 5);
    }

    // ------------------------------------------------------- shop by make

    public function test_make_buttons_follow_sort_order(): void
    {
        $third = $this->category('Volkswagen', 3);
        $first = $this->category('Chevrolet', 1);
        $second = $this->category('Fiat', 2);
        foreach ([$third, $first, $second] as $category) {
            Car::factory()->for($category)->create();
        }

        Livewire::test(Inventory::class)
            ->assertSeeInOrder(['All', 'Chevrolet', 'Fiat', 'Volkswagen']);
    }

    public function test_make_counts_only_active_cars(): void
    {
        $jeep = $this->category('Jeep');
        Car::factory()->count(2)->for($jeep)->create();
        Car::factory()->for($jeep)->inactive()->create();
        Car::factory()->for($jeep)->sold()->create();

        Livewire::test(Inventory::class)
            ->assertViewHas('makes', fn ($makes) => $makes->firstWhere('name', 'Jeep')->active_cars_count === 2)
            ->assertSeeHtml('<small>2</small>');
    }

    public function test_all_button_counts_every_active_car(): void
    {
        Car::factory()->count(4)->create();
        Car::factory()->inactive()->create();

        Livewire::test(Inventory::class)->assertViewHas('totalActive', 4);
    }

    public function test_category_without_active_cars_has_no_button(): void
    {
        $this->category('Ghost');
        $hidden = $this->category('Hidden', 2);
        Car::factory()->for($hidden)->inactive()->create();
        Car::factory()->create();

        Livewire::test(Inventory::class)->assertDontSee('Ghost')->assertDontSee('Hidden');
    }

    public function test_selecting_make_filters_grid_and_marks_button_pressed(): void
    {
        $jeep = $this->category('Jeep');
        $fiat = $this->category('Fiat', 2);
        Car::factory()->for($jeep)->create(['title' => '2019 Jeep Compass']);
        Car::factory()->for($fiat)->create(['title' => '2016 Fiat Toro']);

        Livewire::test(Inventory::class)
            ->call('selectMake', 'jeep')
            ->assertSee('2019 Jeep Compass')
            ->assertDontSee('2016 Fiat Toro')
            ->assertSeeHtml('aria-pressed="true"');
    }

    public function test_sold_car_leaves_make_counts(): void
    {
        $jeep = $this->category('Jeep');
        $car = Car::factory()->for($jeep)->create();

        Livewire::test(Inventory::class)->assertSee('Jeep');

        $car->markAsSold();

        Livewire::test(Inventory::class)->assertDontSee('Jeep')->assertViewHas('total', 0);
    }

    // ------------------------------------------------------------- empty state

    public function test_empty_state_shows_whatsapp_link(): void
    {
        Setting::factory()->create(['whatsapp' => '15551234567']);

        Livewire::test(Inventory::class)
            ->assertSee('No cars match these filters yet.')
            ->assertSeeHtml('https://wa.me/15551234567?text=')
            ->assertDontSee('Show more cars');
    }

    public function test_clearing_filters_restores_cars(): void
    {
        $jeep = $this->category('Jeep');
        Car::factory()->for($jeep)->create(['price' => 20000]);

        Livewire::test(Inventory::class)
            ->set('max', '10000')->assertViewHas('total', 0)->assertSee('No cars match')
            ->set('max', '')->assertViewHas('total', 1)->assertDontSee('No cars match');
    }

    // ----------------------------------------------------------- query string

    public function test_query_string_applies_filters(): void
    {
        $jeep = $this->category('Jeep');
        $suv = $this->bodyType('SUV');
        $match = Car::factory()->for($jeep)->create(['body_type_id' => $suv->id, 'price' => 14000]);
        Car::factory()->create(['price' => 30000]);

        Livewire::withQueryParams(['make' => 'jeep', 'body' => 'suv', 'max' => '15000'])
            ->test(Inventory::class)
            ->assertSet('make', 'jeep')
            ->assertViewHas('cars', fn ($cars) => $cars->pluck('id')->all() === [$match->id]);
    }

    public function test_page_param_shows_n_times_12_cars(): void
    {
        Car::factory()->count(30)->create();

        Livewire::withQueryParams(['page' => '2'])->test(Inventory::class)->assertViewHas('shown', 24);
    }

    public function test_invalid_query_values_do_not_error(): void
    {
        Car::factory()->count(3)->create();

        Livewire::withQueryParams(['make' => '<script>', 'body' => '%00', 'max' => 'NaN', 'page' => 'x'])
            ->test(Inventory::class)
            ->assertOk()
            ->assertViewHas('total', 3);

        $this->get('/?make=zzz&max=abc&page=-1&body=1')->assertOk();
    }

    public function test_filters_are_bound_to_the_url(): void
    {
        $reflection = new \ReflectionClass(Inventory::class);

        foreach (['make' => 'make', 'body' => 'body', 'max' => 'max', 'page' => 'page'] as $property => $alias) {
            $attributes = $reflection->getProperty($property)->getAttributes(\Livewire\Attributes\Url::class);
            $this->assertCount(1, $attributes, "{$property} is not bound to the URL");
            $this->assertSame($alias, $attributes[0]->newInstance()->as);
        }
    }

    // ------------------------------------------------------------- performance

    public function test_home_query_count_does_not_grow_with_cars(): void
    {
        Setting::factory()->create();
        Car::factory()->count(5)->create();
        $this->get('/')->assertOk(); // warm the settings cache
        $few = $this->queriesFor('/');

        Car::factory()->count(25)->create();
        $many = $this->queriesFor('/');

        $this->assertSame($few, $many, 'The number of queries grows with the number of cars');
    }

    private function queriesFor(string $uri): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($uri)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
