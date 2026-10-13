<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Cars\CarResource;
use App\Models\Car;
use App\Support\Format;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The five cars sold most recently.
 */
class LatestSales extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected static ?string $heading = 'Latest sales';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Car::query()->sold()->with('photos')->orderByDesc('sold_at')->orderByDesc('id')->limit(5))
            ->paginated(false)
            ->recordUrl(fn (Car $record): string => CarResource::getUrl('edit', ['record' => $record]))
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->state(fn (Car $record): ?string => $record->photos->first()?->thumbUrl())
                    ->imageWidth(64)
                    ->imageHeight(48)
                    ->extraImgAttributes(['class' => 'rounded-md object-cover'])
                    ->defaultImageUrl(fn (): string => asset('images/no-photo.svg')),
                TextColumn::make('title')
                    ->label('Car')
                    ->weight('bold')
                    ->description(fn (Car $record): ?string => $record->trim),
                TextColumn::make('sold_price')
                    ->label('Sold for')
                    ->state(fn (Car $record): string => Format::price($record->sold_price))
                    ->weight('extrabold')
                    ->description(fn (Car $record): string => $record->sold_at->timezone(config('marios.timezone'))->format('M j, Y')),
            ])
            ->emptyStateHeading('No sales yet')
            ->emptyStateDescription('Mark a car as sold and it shows up here.');
    }
}
