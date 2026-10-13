<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Cars\Pages\EditCar;
use App\Filament\Resources\Cars\Pages\ListCars;
use App\Models\Car;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

class CarListTest extends TestCase
{
    use MakesImages;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());
        Setting::factory()->create();
    }

    // -------------------------------------------------------------------- list

    public function test_list_shows_active_hidden_and_sold_cars(): void
    {
        $active = Car::factory()->create();
        $hidden = Car::factory()->inactive()->create();
        $sold = Car::factory()->sold()->create();

        Livewire::test(ListCars::class)
            ->assertCanSeeTableRecords([$active, $hidden, $sold])
            ->assertCountTableRecords(3);
    }

    public function test_list_shows_the_main_data_of_each_car(): void
    {
        Car::factory()->priceDrop()->create([
            'title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4', 'price' => 18900, 'mileage' => 58200,
            'category_id' => Category::factory()->create(['name' => 'Jeep']),
        ]);

        $this->get('/admin/cars')
            ->assertOk()
            ->assertSee('2019 Jeep Compass')
            ->assertSee('Limited 4x4')
            ->assertSee('$18,900')
            ->assertSee('Jeep · 58,200 mi · 1 photo')
            ->assertSee('Price drop');
    }

    public function test_status_badges_show_hidden_and_sold_cars(): void
    {
        Car::factory()->inactive()->create(['title' => '2010 Hidden One']);
        Car::factory()->sold()->create(['title' => '2011 Sold One']);
        Car::factory()->create(['title' => '2012 Live One']);

        $html = $this->get('/admin/cars')->getContent();

        $this->assertStringContainsString('Hidden from site', $html);
        $this->assertSame(1, substr_count($html, 'Hidden from site'));
    }

    public function test_photo_count_uses_the_right_plural(): void
    {
        Car::factory()->withPhotos(3)->create(['title' => '2010 Three Photos']);
        Car::factory()->inactive()->withoutPhotos()->create(['title' => '2011 No Photos']);

        $this->get('/admin/cars')->assertSee('3 photos')->assertSee('0 photos');
    }

    public function test_sold_cars_show_the_sale_date_and_price(): void
    {
        Car::factory()->sold(Carbon::parse('2026-10-02 12:00', 'America/New_York'), 6495)->create(['price' => 6995]);

        $this->get('/admin/cars')->assertSee('$6,995')->assertSee('Sold for $6,495 · Oct 2, 2026');
    }

    public function test_list_does_not_query_per_row(): void
    {
        Car::factory()->count(3)->create();
        Livewire::test(ListCars::class); // warm up caches
        $few = $this->queriesForList();

        Car::factory()->count(7)->create();
        $many = $this->queriesForList();

        $this->assertSame($few, $many, 'The list runs more queries as cars are added');
    }

    private function queriesForList(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(ListCars::class);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_empty_list_has_a_helpful_message(): void
    {
        $this->get('/admin/cars')->assertSee('No cars yet')->assertSee('Add your first car');
    }

    public function test_list_has_an_add_car_button(): void
    {
        $this->get('/admin/cars')->assertSee('Add car')->assertSee('/admin/cars/create', false);
    }

    // -------------------------------------------------------- search and filter

    public function test_search_matches_title_and_trim(): void
    {
        $compass = Car::factory()->create(['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4']);
        $onix = Car::factory()->create(['title' => '2016 Chevrolet Onix', 'trim' => 'LT']);
        $toro = Car::factory()->create(['title' => '2020 Fiat Toro', 'trim' => 'Volcano']);

        Livewire::test(ListCars::class)
            ->searchTable('Compass')->assertCanSeeTableRecords([$compass])->assertCanNotSeeTableRecords([$onix, $toro])
            ->searchTable('volcano')->assertCanSeeTableRecords([$toro])->assertCanNotSeeTableRecords([$compass, $onix])
            ->searchTable('')->assertCountTableRecords(3);
    }

    public function test_category_filter_limits_the_list(): void
    {
        $jeep = Category::factory()->create(['name' => 'Jeep']);
        $fiat = Category::factory()->create(['name' => 'Fiat']);
        $a = Car::factory()->for($jeep)->create();
        $b = Car::factory()->for($fiat)->create();

        Livewire::test(ListCars::class)
            ->filterTable('category', $jeep->id)
            ->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b]);
    }

    public function test_status_filter_on_site_shows_active_only(): void
    {
        [$active, $hidden, $sold] = [Car::factory()->create(), Car::factory()->inactive()->create(), Car::factory()->sold()->create()];

        Livewire::test(ListCars::class)
            ->filterTable('status', 'on_site')
            ->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$hidden, $sold]);
    }

    public function test_status_filter_hidden_excludes_sold(): void
    {
        [$active, $hidden, $sold] = [Car::factory()->create(), Car::factory()->inactive()->create(), Car::factory()->sold()->create()];

        Livewire::test(ListCars::class)
            ->filterTable('status', 'hidden')
            ->assertCanSeeTableRecords([$hidden])->assertCanNotSeeTableRecords([$active, $sold]);
    }

    public function test_status_filter_sold_shows_sold_with_date_and_price(): void
    {
        [$active, $hidden, $sold] = [Car::factory()->create(), Car::factory()->inactive()->create(), Car::factory()->sold(now()->subDays(3), 9100)->create()];

        Livewire::test(ListCars::class)
            ->filterTable('status', 'sold')
            ->assertCanSeeTableRecords([$sold])->assertCanNotSeeTableRecords([$active, $hidden])
            ->assertSee('Sold for $9,100');
    }

    public function test_without_a_status_filter_every_car_is_listed(): void
    {
        Car::factory()->create();
        Car::factory()->inactive()->create();
        Car::factory()->sold()->create();

        Livewire::test(ListCars::class)->assertCountTableRecords(3);
    }

    public function test_status_filter_combines_with_search_and_category(): void
    {
        $jeep = Category::factory()->create(['name' => 'Jeep']);
        $fiat = Category::factory()->create(['name' => 'Fiat']);
        $match = Car::factory()->for($jeep)->sold()->create(['title' => '2019 Jeep Compass']);
        $wrongStatus = Car::factory()->for($jeep)->create(['title' => '2018 Jeep Compass']);
        $wrongCategory = Car::factory()->for($fiat)->sold()->create(['title' => '2019 Fiat Compass']);
        $wrongName = Car::factory()->for($jeep)->sold()->create(['title' => '2015 Jeep Renegade']);

        Livewire::test(ListCars::class)
            ->filterTable('status', 'sold')
            ->filterTable('category', $jeep->id)
            ->searchTable('Compass')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$wrongStatus, $wrongCategory, $wrongName]);
    }

    // ------------------------------------------------------------ on-site switch

    public function test_toggle_off_hides_car_from_site_and_counts(): void
    {
        $car = Car::factory()->for(Category::factory()->create(['name' => 'Jeep']))->create(['title' => '2019 Jeep Compass']);
        $this->get('/')->assertSee('2019 Jeep Compass')->assertSee('Jeep');

        Livewire::test(ListCars::class)->call('updateTableColumnState', 'is_active', (string) $car->getKey(), false);

        $this->assertFalse($car->fresh()->is_active);
        $this->get('/')->assertDontSee('2019 Jeep Compass')->assertDontSee('Jeep');
    }

    public function test_toggle_on_puts_a_complete_car_on_the_site(): void
    {
        $car = Car::factory()->inactive()->withPhotos(1)->create(['title' => '2019 Jeep Compass']);

        Livewire::test(ListCars::class)->call('updateTableColumnState', 'is_active', (string) $car->getKey(), true);

        $this->assertTrue($car->fresh()->is_active);
        $this->get('/')->assertSee('2019 Jeep Compass');
    }

    public function test_toggle_on_without_photo_is_blocked_with_a_message(): void
    {
        $car = Car::factory()->inactive()->withoutPhotos()->create();

        Livewire::test(ListCars::class)
            ->call('updateTableColumnState', 'is_active', (string) $car->getKey(), true)
            ->assertNotified('This car cannot go on the website yet');

        $this->assertFalse($car->fresh()->is_active);
    }

    public function test_toggle_on_without_price_is_blocked_with_a_message(): void
    {
        $car = Car::factory()->inactive()->withoutPrice()->create();

        $component = Livewire::test(ListCars::class)
            ->call('updateTableColumnState', 'is_active', (string) $car->getKey(), true)
            ->assertNotified('This car cannot go on the website yet');

        $this->assertFalse($car->fresh()->is_active);
    }

    public function test_toggle_on_sold_car_is_blocked_and_asks_to_reopen(): void
    {
        $car = Car::factory()->sold()->create();

        Livewire::test(ListCars::class)
            ->call('updateTableColumnState', 'is_active', (string) $car->getKey(), true)
            ->assertNotified('This car cannot go on the website yet');

        $fresh = $car->fresh();
        $this->assertFalse($fresh->is_active);
        $this->assertTrue($fresh->isSold());
    }

    // -------------------------------------------------------------- edit, delete

    public function test_each_row_links_to_the_edit_page(): void
    {
        $car = Car::factory()->create();

        $this->get('/admin/cars')->assertSee("/admin/cars/{$car->id}/edit", false);
    }

    public function test_delete_asks_for_confirmation_before_doing_anything(): void
    {
        $car = Car::factory()->create();

        Livewire::test(ListCars::class)
            ->mountAction(TestAction::make(DeleteAction::class)->table($car))
            ->assertActionMounted(TestAction::make(DeleteAction::class)->table($car));

        $this->assertDatabaseHas('cars', ['id' => $car->id]);
    }

    public function test_delete_removes_car_photos_and_files(): void
    {
        $car = Car::factory()->withPhotos(2)->create();
        foreach ($car->photos as $photo) {
            Storage::disk('public')->put($photo->file_path, $this->jpegBytes(800, 600));
        }
        $paths = $car->photos->pluck('file_path')->all();

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($car))
            ->assertNotified('Car deleted');

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        $this->assertDatabaseCount('car_photos', 0);
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_delete_keeps_the_category(): void
    {
        $category = Category::factory()->create();
        $car = Car::factory()->for($category)->create();

        Livewire::test(ListCars::class)->callAction(TestAction::make(DeleteAction::class)->table($car));

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_deleting_a_sold_car_warns_that_the_sale_is_removed(): void
    {
        $car = Car::factory()->sold()->create();

        $component = Livewire::test(ListCars::class)->mountAction(TestAction::make(DeleteAction::class)->table($car));

        $this->assertStringContainsString('Deleting it also removes the sale from the sales numbers on the dashboard', $this->modalDescription($component));
    }

    public function test_deleting_an_unsold_car_suggests_hiding_it_instead(): void
    {
        $car = Car::factory()->create();

        $component = Livewire::test(ListCars::class)->mountAction(TestAction::make(DeleteAction::class)->table($car));

        $this->assertStringContainsString('turn off "Show on website" instead', $this->modalDescription($component));
    }

    private function modalDescription($component): string
    {
        return (string) $component->instance()->getMountedAction()->getModalDescription();
    }

    // ------------------------------------------------------------------- sales

    public function test_mark_as_sold_action_updates_car(): void
    {
        $car = Car::factory()->create(['price' => 12995]);

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => now('America/New_York')->toDateString(), 'sold_price' => 12000])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $fresh = $car->fresh();
        $this->assertTrue($fresh->isSold());
        $this->assertSame(12000, $fresh->sold_price);
        $this->assertFalse($fresh->is_active);
    }

    public function test_mark_as_sold_form_starts_with_today_and_the_listed_price(): void
    {
        $car = Car::factory()->create(['price' => 12995]);

        Livewire::test(ListCars::class)
            ->mountAction(TestAction::make('markAsSold')->table($car))
            ->assertActionDataSet([
                'sold_at' => now('America/New_York')->toDateString(),
                'sold_price' => 12995,
            ]);
    }

    public function test_sold_car_disappears_from_home_counts_and_sitemap(): void
    {
        $category = Category::factory()->create(['name' => 'Jeep']);
        $car = Car::factory()->for($category)->create(['title' => '2019 Jeep Compass']);
        $slug = $car->fresh()->slug;
        $this->get('/')->assertSee('2019 Jeep Compass')->assertSee('Jeep');
        $this->get('/sitemap.xml')->assertSee($slug);

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => now('America/New_York')->toDateString(), 'sold_price' => 17000]);

        $this->get('/')->assertDontSee('2019 Jeep Compass')->assertDontSee('Jeep')->assertSee('0 cars');
        $this->get('/sitemap.xml')->assertDontSee($slug);
        $this->get("/cars/{$slug}")->assertNotFound();
    }

    public function test_mark_as_sold_rejects_a_future_date(): void
    {
        $car = Car::factory()->create();

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => now('America/New_York')->addDays(2)->toDateString(), 'sold_price' => 9000])
            ->assertHasFormErrors(['sold_at']);

        $this->assertFalse($car->fresh()->isSold());
    }

    public function test_mark_as_sold_rejects_a_missing_or_zero_price(): void
    {
        $car = Car::factory()->create();
        $today = now('America/New_York')->toDateString();

        foreach ([null, 0, -5, 'abc', 12.5] as $bad) {
            Livewire::test(ListCars::class)
                ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => $today, 'sold_price' => $bad])
                ->assertHasFormErrors(['sold_price']);
        }

        $this->assertFalse($car->fresh()->isSold());
    }

    public function test_a_past_date_is_stored_as_noon_in_the_store_timezone(): void
    {
        $car = Car::factory()->create();

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => '2026-10-02', 'sold_price' => 9000]);

        $soldAt = $car->fresh()->sold_at->timezone('America/New_York');
        $this->assertSame('2026-10-02 12:00', $soldAt->format('Y-m-d H:i'));
    }

    public function test_the_action_is_not_offered_for_a_sold_car(): void
    {
        $sold = Car::factory()->sold()->create();
        $live = Car::factory()->create();

        Livewire::test(ListCars::class)
            ->assertActionHidden(TestAction::make('markAsSold')->table($sold))
            ->assertActionVisible(TestAction::make('markAsSold')->table($live));
    }

    public function test_mark_as_sold_twice_does_not_duplicate_the_sale(): void
    {
        $car = Car::factory()->create();
        $car->markAsSold(now()->subDays(2), 9500);
        $first = $car->fresh()->sold_at;

        $car->fresh()->markAsSold(now(), 100);

        $this->assertTrue($car->fresh()->sold_at->equalTo($first));
        $this->assertSame(9500, $car->fresh()->sold_price);
    }

    public function test_reopen_action_clears_the_sale_and_keeps_the_car_hidden(): void
    {
        $car = Car::factory()->sold()->create();

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsAvailable')->table($car))
            ->assertNotified($car->title.' is available again');

        $fresh = $car->fresh();
        $this->assertFalse($fresh->isSold());
        $this->assertNull($fresh->sold_price);
        $this->assertFalse($fresh->is_active);
        $this->get('/cars/'.$fresh->slug)->assertNotFound();
    }

    public function test_reopen_is_not_offered_for_an_unsold_car(): void
    {
        $car = Car::factory()->create();

        Livewire::test(ListCars::class)->assertActionHidden(TestAction::make('markAsAvailable')->table($car));
    }

    public function test_a_reopened_car_can_go_back_on_the_site(): void
    {
        $car = Car::factory()->sold()->withPhotos(1)->create(['title' => '2019 Jeep Compass']);
        $car->reopen();

        Livewire::test(ListCars::class)->call('updateTableColumnState', 'is_active', (string) $car->getKey(), true);

        $this->get('/')->assertSee('2019 Jeep Compass');
    }

    // ------------------------------------------------------------ on the edit page

    public function test_edit_page_offers_mark_as_sold_and_the_form_shows_the_car_off(): void
    {
        $car = Car::factory()->create();

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->assertActionVisible('markAsSold')
            ->assertActionHidden('markAsAvailable')
            ->callAction('markAsSold', ['sold_at' => now('America/New_York')->toDateString(), 'sold_price' => 11000])
            ->assertFormSet(['is_active' => false])
            ->assertActionHidden('markAsSold')
            ->assertActionVisible('markAsAvailable')
            ->assertFormFieldDisabled('is_active');

        $this->assertTrue($car->fresh()->isSold());
    }

    public function test_edit_page_reopen_returns_the_car_hidden(): void
    {
        $car = Car::factory()->sold()->create();

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->callAction('markAsAvailable')
            ->assertFormSet(['is_active' => false])
            ->assertActionVisible('markAsSold');

        $this->assertFalse($car->fresh()->isSold());
    }
}
