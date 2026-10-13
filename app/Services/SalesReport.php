<?php

namespace App\Services;

use App\Models\Car;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The numbers on the admin dashboard, read straight from the cars (no report
 * table): `sold_at` and `sold_price` for sales, `is_active` for the stock.
 *
 * A month is a calendar month in the store's timezone (America/New_York), so a
 * sale made at 11:30 PM on the last day still counts in that month even though
 * it is already the next day in UTC, the timezone the database uses.
 */
class SalesReport
{
    public function timezone(): string
    {
        return config('marios.timezone');
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    /**
     * Sales in the calendar month that contains $inMonth.
     *
     * @return array{label: string, count: int, total: int, start: CarbonImmutable}
     */
    public function month(?CarbonImmutable $inMonth = null): array
    {
        $start = ($inMonth ?? $this->now())->setTimezone($this->timezone())->startOfMonth();

        $row = Car::sold()
            ->where('sold_at', '>=', $start->utc())
            ->where('sold_at', '<', $start->addMonth()->utc())
            ->selectRaw('count(*) as sold_count, coalesce(sum(sold_price), 0) as sold_total')
            ->first();

        return [
            'label' => $start->format('F'),
            'count' => (int) $row->sold_count,
            'total' => (int) $row->sold_total,
            'start' => $start,
        ];
    }

    /** @return array{label: string, count: int, total: int, start: CarbonImmutable} */
    public function currentMonth(): array
    {
        return $this->month();
    }

    /** @return array{label: string, count: int, total: int, start: CarbonImmutable} */
    public function previousMonth(): array
    {
        return $this->month($this->now()->startOfMonth()->subMonth());
    }

    /**
     * Percentage change from $previous to $current, rounded. Null when there
     * is nothing to compare with (a previous month without sales).
     */
    public static function change(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    /**
     * One entry per month, oldest first, ending with the current month. Months
     * without sales are present with zeros.
     *
     * @return list<array{key: string, label: string, count: int, total: int}>
     */
    public function lastMonths(int $months = 6): array
    {
        $first = $this->now()->startOfMonth()->subMonths($months - 1);

        $buckets = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $first->addMonths($i);
            $buckets[$month->format('Y-m')] = ['key' => $month->format('Y-m'), 'label' => $month->format('M Y'), 'count' => 0, 'total' => 0];
        }

        Car::sold()
            ->where('sold_at', '>=', $first->utc())
            ->where('sold_at', '<', $first->addMonths($months)->utc())
            ->get(['sold_at', 'sold_price'])
            ->each(function (Car $car) use (&$buckets): void {
                $key = $car->sold_at->copy()->timezone($this->timezone())->format('Y-m');

                if (isset($buckets[$key])) {
                    $buckets[$key]['count']++;
                    $buckets[$key]['total'] += (int) $car->sold_price;
                }
            });

        return array_values($buckets);
    }

    /**
     * Cars on the site, hidden cars and the listed value of what is on the
     * site. Sold cars are in none of them.
     *
     * @return array{on_site: int, hidden: int, value: int}
     */
    public function inventory(): array
    {
        return [
            'on_site' => Car::active()->count(),
            'hidden' => Car::hidden()->count(),
            'value' => (int) Car::active()->sum('price'),
        ];
    }

    /** @return Collection<int, Car> */
    public function latestSales(int $limit = 5): Collection
    {
        return Car::sold()->with('photos')->orderByDesc('sold_at')->orderByDesc('id')->limit($limit)->get();
    }
}
