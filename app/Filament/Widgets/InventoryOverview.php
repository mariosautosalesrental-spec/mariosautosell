<?php

namespace App\Filament\Widgets;

use App\Services\SalesReport;
use App\Support\Format;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * What is on the lot right now. Sold cars are not part of it.
 */
class InventoryOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $inventory = app(SalesReport::class)->inventory();

        return [
            Stat::make('On the website', (string) $inventory['on_site'])
                ->description($inventory['on_site'] === 1 ? 'car for sale' : 'cars for sale')
                ->color('success'),
            Stat::make('Hidden', (string) $inventory['hidden'])
                ->description('saved but not on the website')
                ->color('gray'),
            Stat::make('Value on the website', Format::price($inventory['value']))
                ->description('sum of the listed prices'),
        ];
    }
}
