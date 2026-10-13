<?php

namespace App\Filament\Resources\Cars\Tables;

use App\Filament\Actions\MarkAsAvailableAction;
use App\Filament\Actions\MarkAsSoldAction;
use App\Models\Car;
use App\Support\Format;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * The cars list: a card per car on phones, a row on desktop, with the
 * On site / Hidden switch right in the list.
 */
class CarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'photos'])->withCount('photos'))
            ->searchPlaceholder('Search by name')
            ->columns([
                Split::make([
                    ImageColumn::make('cover')
                        ->label('Photo')
                        ->state(fn (Car $record): ?string => $record->photos->first()?->thumbUrl())
                        ->imageWidth(96)
                        ->imageHeight(72)
                        ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                        ->defaultImageUrl(fn (): string => asset('images/no-photo.svg'))
                        ->grow(false),

                    Stack::make([
                        TextColumn::make('title')
                            ->label('Car')
                            ->searchable(['title', 'trim'])
                            ->sortable()
                            ->weight('bold')
                            ->description(fn (Car $record): ?string => $record->trim),
                        TextColumn::make('details')
                            ->state(fn (Car $record): string => collect([
                                $record->category->name,
                                Format::miles($record->mileage),
                                $record->photos_count.' '.($record->photos_count === 1 ? 'photo' : 'photos'),
                            ])->filter()->implode(' · '))
                            ->color('gray')
                            ->size('sm'),
                        TextColumn::make('badges')
                            ->label('Status')
                            ->badge()
                            ->state(fn (Car $record): array => self::badges($record))
                            ->color(fn (string $state): string => match ($state) {
                                'Sold' => 'success',
                                'Hidden from site' => 'gray',
                                default => 'danger', // Price drop
                            }),
                    ])->space(1),

                    Stack::make([
                        TextColumn::make('price')
                            ->label('Price')
                            ->sortable()
                            ->weight('extrabold')
                            ->size('lg')
                            ->state(fn (Car $record): string => Format::price($record->price) ?: '—')
                            ->description(fn (Car $record): ?string => $record->isSold()
                                ? 'Sold for '.Format::price($record->sold_price).' · '.$record->sold_at->timezone(config('marios.timezone'))->format('M j, Y')
                                : null),
                    ])->grow(false)->alignment('end'),

                    ToggleColumn::make('is_active')
                        ->label('On site')
                        ->onColor('success')
                        ->updateStateUsing(fn (Car $record, bool $state): bool => self::switchSite($record, $state))
                        ->grow(false),
                ])->from('md'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('category', 'name', fn (Builder $query) => $query->ordered()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('All')
                    ->options([
                        'on_site' => 'On site',
                        'hidden' => 'Hidden',
                        'sold' => 'Sold',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'on_site' => $query->active(),
                        'hidden' => $query->hidden(),
                        'sold' => $query->sold(),
                        default => $query,
                    }),
            ])
            ->recordActions([
                MarkAsSoldAction::make(),
                MarkAsAvailableAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(fn (Car $record): string => 'Delete '.$record->title.'?')
                    ->modalDescription(fn (Car $record): string => $record->isSold()
                        ? 'This car is sold. Deleting it also removes the sale from the sales numbers on the dashboard, and its photos are deleted.'
                        : 'The car and its photos are deleted. To keep it, turn off "Show on website" instead.')
                    ->successNotificationTitle('Car deleted'),
            ])
            ->emptyStateHeading('No cars yet')
            ->emptyStateDescription('Add your first car and it shows up on the website.');
    }

    /** @return list<string> */
    private static function badges(Car $car): array
    {
        return array_values(array_filter([
            $car->isSold() ? 'Sold' : ($car->is_active ? null : 'Hidden from site'),
            $car->price_drop ? 'Price drop' : null,
        ]));
    }

    /**
     * Turns a car on or off from the list. Turning on is refused, with the
     * reason, when the car has no price, no photo or is sold.
     */
    private static function switchSite(Car $car, bool $on): bool
    {
        if (! $on) {
            $car->deactivate();

            return false;
        }

        try {
            $car->activate();
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('This car cannot go on the website yet')
                ->body(collect($e->errors())->flatten()->first())
                ->send();

            return false;
        }

        return true;
    }
}
