<?php

namespace Tests\Feature\Dashboard;

use App\Models\Car;
use App\Services\SalesReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private function report(): SalesReport
    {
        return app(SalesReport::class);
    }

    private function now(string $time): void
    {
        $this->travelTo(CarbonImmutable::parse($time, 'America/New_York'));
    }

    private function sold(string $when, int $price, array $attributes = []): Car
    {
        return Car::factory()->sold(CarbonImmutable::parse($when, 'America/New_York'), $price)->create($attributes);
    }

    public function test_month_totals_use_sold_price_not_listed_price(): void
    {
        $this->now('2026-10-15 12:00');
        $this->sold('2026-10-03 10:00', 9500, ['price' => 11000]);
        $this->sold('2026-10-10 10:00', 14000, ['price' => 15000]);

        $month = $this->report()->currentMonth();

        $this->assertSame(2, $month['count']);
        $this->assertSame(23500, $month['total']);
        $this->assertSame('October', $month['label']);
    }

    public function test_sale_at_month_end_in_new_york_counts_in_that_month(): void
    {
        // 11:30 PM on Oct 31 in New York is already Nov 1 in UTC.
        $this->now('2026-11-15 12:00');
        $this->sold('2026-10-31 23:30', 8000);

        $this->assertSame(1, $this->report()->previousMonth()['count']);
        $this->assertSame(0, $this->report()->currentMonth()['count']);
        $this->assertSame('2026-11-01 03:30:00', Car::sold()->first()->sold_at->format('Y-m-d H:i:s'));
    }

    public function test_sale_just_after_midnight_belongs_to_the_new_month_even_across_the_dst_change(): void
    {
        // Daylight saving time ends on Nov 1, 2026.
        $this->now('2026-11-15 12:00');
        $this->sold('2026-11-01 00:30', 7000);
        $this->sold('2026-10-31 23:59', 6000);

        $this->assertSame(1, $this->report()->currentMonth()['count']);
        $this->assertSame(7000, $this->report()->currentMonth()['total']);
        $this->assertSame(6000, $this->report()->previousMonth()['total']);
    }

    public function test_sales_of_other_months_are_not_counted(): void
    {
        $this->now('2026-10-15 12:00');
        $this->sold('2026-09-30 23:59', 1000);
        $this->sold('2026-10-01 00:00', 2000);
        $this->sold('2026-10-15 11:00', 3000);

        $this->assertSame(2, $this->report()->currentMonth()['count']);
        $this->assertSame(5000, $this->report()->currentMonth()['total']);
        $this->assertSame(1000, $this->report()->previousMonth()['total']);
    }

    public function test_january_looks_back_to_december_of_the_previous_year(): void
    {
        $this->now('2027-01-10 12:00');
        $this->sold('2026-12-20 12:00', 5000);

        $previous = $this->report()->previousMonth();

        $this->assertSame('December', $previous['label']);
        $this->assertSame(5000, $previous['total']);
    }

    public function test_reopened_car_is_excluded_from_sales(): void
    {
        $this->now('2026-10-15 12:00');
        $car = $this->sold('2026-10-05 12:00', 9000);
        $this->assertSame(1, $this->report()->currentMonth()['count']);

        $car->reopen();

        $this->assertSame(0, $this->report()->currentMonth()['count']);
        $this->assertSame(0, $this->report()->currentMonth()['total']);
    }

    public function test_change_is_null_without_a_previous_month_and_never_divides_by_zero(): void
    {
        $this->assertNull(SalesReport::change(5, 0));
        $this->assertNull(SalesReport::change(0, 0));
    }

    public function test_change_is_a_rounded_percentage(): void
    {
        $this->assertSame(50, SalesReport::change(3, 2));
        $this->assertSame(-50, SalesReport::change(1, 2));
        $this->assertSame(0, SalesReport::change(4, 4));
        $this->assertSame(-100, SalesReport::change(0, 3));
        $this->assertSame(33, SalesReport::change(4, 3));
    }

    public function test_six_month_series_includes_zero_months_oldest_first(): void
    {
        $this->now('2026-10-15 12:00');
        $this->sold('2026-10-02 12:00', 1000);
        $this->sold('2026-10-09 12:00', 2000);
        $this->sold('2026-07-20 12:00', 4000);
        $this->sold('2026-04-30 12:00', 9999); // before the window (May–October)

        $series = $this->report()->lastMonths(6);

        $this->assertCount(6, $series);
        $this->assertSame(['2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'], array_column($series, 'key'));
        $this->assertSame(['May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026'], array_column($series, 'label'));
        $this->assertSame([0, 0, 1, 0, 0, 2], array_column($series, 'count'));
        $this->assertSame([0, 0, 4000, 0, 0, 3000], array_column($series, 'total'));
    }

    public function test_six_month_series_crosses_the_year_boundary(): void
    {
        $this->now('2027-02-10 12:00');
        $this->sold('2026-12-15 12:00', 1000);

        $series = $this->report()->lastMonths(6);

        $this->assertSame(['2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02'], array_column($series, 'key'));
        $this->assertSame(1, $series[3]['count']);
    }

    public function test_inventory_counts_exclude_sold_cars(): void
    {
        Car::factory()->count(3)->create(['price' => 10000]);
        Car::factory()->count(2)->inactive()->create(['price' => 7000]);
        Car::factory()->sold()->create(['price' => 5000]);

        $inventory = $this->report()->inventory();

        $this->assertSame(3, $inventory['on_site']);
        $this->assertSame(2, $inventory['hidden']);
        $this->assertSame(30000, $inventory['value']); // only what is on the site
    }

    public function test_inventory_of_an_empty_lot_is_zero(): void
    {
        $this->assertSame(['on_site' => 0, 'hidden' => 0, 'value' => 0], $this->report()->inventory());
    }

    public function test_latest_sales_are_the_five_most_recent_first(): void
    {
        $this->now('2026-10-20 12:00');
        $cars = [];
        foreach ([1, 2, 3, 4, 5, 6, 7] as $day) {
            $cars[$day] = $this->sold("2026-10-0{$day} 12:00", 1000 * $day);
        }
        Car::factory()->create(); // not sold

        $latest = $this->report()->latestSales();

        $this->assertCount(5, $latest);
        $this->assertSame([$cars[7]->id, $cars[6]->id, $cars[5]->id, $cars[4]->id, $cars[3]->id], $latest->pluck('id')->all());
    }

    public function test_an_empty_database_gives_zeros_not_errors(): void
    {
        $month = $this->report()->currentMonth();

        $this->assertSame(0, $month['count']);
        $this->assertSame(0, $month['total']);
        $this->assertSame([], $this->report()->latestSales()->all());
        $this->assertSame(array_fill(0, 6, 0), array_column($this->report()->lastMonths(6), 'count'));
    }
}
