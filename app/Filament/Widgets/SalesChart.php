<?php

namespace App\Filament\Widgets;

use App\Services\SalesReport;
use Filament\Widgets\ChartWidget;

/**
 * Cars sold per month over the last six months, the current one included.
 */
class SalesChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Sales in the last 6 months';

    protected ?string $description = 'Cars sold per month';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = app(SalesReport::class)->lastMonths(6);

        return [
            'datasets' => [
                [
                    'label' => 'Cars sold',
                    'data' => array_column($months, 'count'),
                    'backgroundColor' => '#B3121F',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => array_column($months, 'label'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0, 'stepSize' => 1]],
            ],
        ];
    }
}
