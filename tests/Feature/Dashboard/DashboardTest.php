<?php

namespace Tests\Feature\Dashboard;

use App\Filament\Resources\Cars\Pages\ListCars;
use App\Filament\Widgets\InventoryOverview;
use App\Filament\Widgets\LatestSales;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\SalesThisMonth;
use App\Models\Car;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());
        Setting::factory()->create();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'America/New_York'));
    }

    private function sold(string $when, int $price, array $attributes = []): Car
    {
        return Car::factory()->sold(CarbonImmutable::parse($when, 'America/New_York'), $price)->create($attributes);
    }

    private function chartData(): array
    {
        $component = Livewire::test(SalesChart::class);

        return (fn () => $this->getData())->call($component->instance());
    }

    // --------------------------------------------------------------- the page

    public function test_admin_home_is_the_dashboard_with_the_widgets_in_order(): void
    {
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSeeInOrder(['Cars sold in October', 'On the website', 'Sales in the last 6 months', 'Latest sales']);
    }

    public function test_dashboard_is_the_first_item_of_the_menu(): void
    {
        $html = $this->get('/admin')->getContent();
        $nav = substr($html, strpos($html, 'fi-sidebar-nav'));

        $this->assertLessThan(strpos($nav, 'Cars'), strpos($nav, 'Dashboard'));
    }

    public function test_default_filament_widgets_are_gone(): void
    {
        $this->get('/admin')->assertDontSee('Documentation')->assertDontSee('GitHub');
    }

    // ----------------------------------------------------------- sales widget

    public function test_sales_widget_shows_count_total_and_change(): void
    {
        $this->sold('2026-09-10 12:00', 10000);
        $this->sold('2026-09-12 12:00', 10000);
        $this->sold('2026-10-02 12:00', 12000);
        $this->sold('2026-10-05 12:00', 13000);
        $this->sold('2026-10-09 12:00', 15000);

        Livewire::test(SalesThisMonth::class)
            ->assertSee('Cars sold in October')
            ->assertSee('Sales in October')
            ->assertSee('+50% vs September')   // 3 cars vs 2
            ->assertSee('$40,000')
            ->assertSee('+100% vs September');  // $40,000 vs $20,000
    }

    public function test_sales_widget_shows_a_dash_without_a_previous_month(): void
    {
        $this->sold('2026-10-02 12:00', 12000);

        Livewire::test(SalesThisMonth::class)
            ->assertSee('— vs September')
            ->assertDontSee('NaN')
            ->assertDontSee('INF');
    }

    public function test_sales_widget_shows_a_decrease(): void
    {
        $this->sold('2026-09-10 12:00', 10000);
        $this->sold('2026-09-12 12:00', 10000);
        $this->sold('2026-10-02 12:00', 5000);

        Livewire::test(SalesThisMonth::class)->assertSee('-50% vs September')->assertSee('-75% vs September');
    }

    public function test_sales_widget_with_no_sales_at_all(): void
    {
        Livewire::test(SalesThisMonth::class)->assertSee('Cars sold in October')->assertSee('$0')->assertSee('— vs September');
    }

    public function test_sales_widget_uses_the_sold_price(): void
    {
        $this->sold('2026-10-02 12:00', 8000, ['price' => 11000]);

        Livewire::test(SalesThisMonth::class)->assertSee('$8,000')->assertDontSee('$11,000');
    }

    // ------------------------------------------------------- inventory widget

    public function test_inventory_widget_counts_on_site_and_hidden_cars(): void
    {
        Car::factory()->count(2)->create(['price' => 10000]);
        Car::factory()->inactive()->create();

        Livewire::test(InventoryOverview::class)
            ->assertSee('On the website')
            ->assertSee('Hidden')
            ->assertSee('Value on the website')
            ->assertSee('$20,000')
            ->assertSee('cars for sale');
    }

    public function test_inventory_widget_excludes_sold_cars(): void
    {
        Car::factory()->create(['price' => 10000]);
        $this->sold('2026-10-02 12:00', 50000, ['price' => 60000]);

        Livewire::test(InventoryOverview::class)
            ->assertSee('$10,000')
            ->assertSee('car for sale')
            ->assertDontSee('$60,000')
            ->assertDontSee('$70,000');
    }

    // --------------------------------------------------------------- chart

    public function test_chart_data_has_six_months_with_zeros(): void
    {
        $this->sold('2026-10-02 12:00', 1000);
        $this->sold('2026-10-04 12:00', 1000);
        $this->sold('2026-08-04 12:00', 1000);

        $data = $this->chartData();

        $this->assertSame(['May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026'], $data['labels']);
        $this->assertSame([0, 0, 0, 1, 0, 2], $data['datasets'][0]['data']);
    }

    public function test_chart_is_a_bar_chart_with_a_heading(): void
    {
        Livewire::test(SalesChart::class)->assertSee('Sales in the last 6 months')->assertSee('Cars sold per month');
    }

    // ------------------------------------------------------------ latest sales

    public function test_latest_sales_lists_five_most_recent(): void
    {
        $cars = collect([1, 2, 3, 4, 5, 6])->map(fn ($day) => $this->sold("2026-10-0{$day} 12:00", 1000 * $day, ['title' => "2020 Sold Car {$day}"]));

        Livewire::test(LatestSales::class)
            ->assertCanSeeTableRecords($cars->slice(1)->reverse()->values(), inOrder: true)
            ->assertCanNotSeeTableRecords([$cars[0]]);
    }

    public function test_latest_sales_shows_price_and_date_and_links_to_the_car(): void
    {
        $car = $this->sold('2026-10-02 12:00', 8500, ['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4']);

        Livewire::test(LatestSales::class)
            ->assertSee('2019 Jeep Compass')
            ->assertSee('Limited 4x4')
            ->assertSee('$8,500')
            ->assertSee('Oct 2, 2026')
            ->assertSeeHtml("/admin/cars/{$car->id}/edit");
    }

    public function test_latest_sales_empty_state(): void
    {
        Car::factory()->create(); // on the lot, not sold

        Livewire::test(LatestSales::class)->assertSee('No sales yet')->assertSee('Mark a car as sold');
    }

    // ----------------------------------------------------- live with the actions

    public function test_marking_a_car_sold_updates_the_dashboard_numbers(): void
    {
        $car = Car::factory()->create(['price' => 12000, 'title' => '2019 Jeep Compass']);
        $other = Car::factory()->create(['price' => 8000]);

        Livewire::test(SalesThisMonth::class)->assertSee('$0');
        Livewire::test(InventoryOverview::class)->assertSee('$20,000');

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => '2026-10-15', 'sold_price' => 11500]);

        Livewire::test(SalesThisMonth::class)->assertSee('$11,500')->assertSee('— vs September');
        Livewire::test(InventoryOverview::class)->assertSee('$8,000')->assertDontSee('$20,000');
        Livewire::test(LatestSales::class)->assertSee('2019 Jeep Compass');
        $this->assertSame(1, $this->chartData()['datasets'][0]['data'][5]);
    }

    public function test_reopening_a_car_reverts_the_dashboard_numbers(): void
    {
        $car = $this->sold('2026-10-10 12:00', 9000, ['title' => '2019 Jeep Compass']);
        Livewire::test(SalesThisMonth::class)->assertSee('$9,000');

        Livewire::test(ListCars::class)->callAction(TestAction::make('markAsAvailable')->table($car));

        Livewire::test(SalesThisMonth::class)->assertDontSee('$9,000')->assertSee('$0');
        Livewire::test(LatestSales::class)->assertDontSee('2019 Jeep Compass');
        $this->assertSame(0, $this->chartData()['datasets'][0]['data'][5]);
    }

    public function test_deleting_a_sold_car_removes_the_sale_from_the_numbers(): void
    {
        $car = $this->sold('2026-10-10 12:00', 9000);

        Livewire::test(ListCars::class)->callAction(TestAction::make(DeleteAction::class)->table($car));

        Livewire::test(SalesThisMonth::class)->assertDontSee('$9,000');
    }

    public function test_a_sale_made_by_the_action_today_lands_in_the_current_month_in_new_york(): void
    {
        // 11:30 PM on the last day of the month in New York (already the next day in UTC).
        $this->travelTo(CarbonImmutable::parse('2026-10-31 23:30', 'America/New_York'));
        $car = Car::factory()->create(['price' => 9000]);

        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => '2026-10-31', 'sold_price' => 9000]);

        Livewire::test(SalesThisMonth::class)->assertSee('Cars sold in October')->assertSee('$9,000');
    }
}
