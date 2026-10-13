<?php

namespace App\Filament\Widgets;

use App\Services\SalesReport;
use App\Support\Format;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Cars sold and money sold this month, each compared with last month.
 */
class SalesThisMonth extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $report = app(SalesReport::class);
        $current = $report->currentMonth();
        $previous = $report->previousMonth();

        return [
            $this->stat('Cars sold in '.$current['label'], (string) $current['count'], $current['count'], $previous['count'], $previous['label']),
            $this->stat('Sales in '.$current['label'], Format::price($current['total']), $current['total'], $previous['total'], $previous['label']),
        ];
    }

    private function stat(string $label, string $value, int $current, int $previous, string $previousLabel): Stat
    {
        $change = SalesReport::change($current, $previous);

        $description = match (true) {
            $change === null => "— vs {$previousLabel}",
            $change > 0 => "+{$change}% vs {$previousLabel}",
            $change < 0 => "{$change}% vs {$previousLabel}",
            default => "Same as {$previousLabel}",
        };

        return Stat::make($label, $value)
            ->description($description)
            ->descriptionIcon(match (true) {
                $change === null || $change === 0 => null,
                $change > 0 => Heroicon::ArrowTrendingUp,
                default => Heroicon::ArrowTrendingDown,
            })
            ->color(match (true) {
                $change === null || $change === 0 => 'gray',
                $change > 0 => 'success',
                default => 'danger',
            });
    }
}
