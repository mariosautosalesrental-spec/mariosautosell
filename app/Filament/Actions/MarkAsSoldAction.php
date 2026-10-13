<?php

namespace App\Filament\Actions;

use App\Models\Car;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * "Mark as sold": takes the car off the site and records the sale, asking only
 * for the date (today by default) and the price (the listed price by default).
 */
class MarkAsSoldAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'markAsSold';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Mark as sold')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn (Car $record): bool => ! $record->isSold())
            ->modalHeading(fn (Car $record): string => 'Mark '.$record->title.' as sold')
            ->modalDescription('The car comes off the website and counts in the sales of the month.')
            ->modalSubmitActionLabel('Mark as sold')
            ->modalWidth('md')
            ->schema([
                DatePicker::make('sold_at')
                    ->label('Sale date')
                    ->native()
                    ->required()
                    ->maxDate(fn (): string => static::today()->toDateString())
                    ->default(fn (): string => static::today()->toDateString()),
                TextInput::make('sold_price')
                    ->label('Sale price')
                    ->helperText('Starts as the listed price. Change it if the car sold for less.')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(9999999)
                    ->prefix('$')
                    ->required()
                    ->default(fn (Car $record): ?int => $record->price),
            ])
            ->action(function (Car $record, array $data): void {
                $record->markAsSold(
                    static::instantFor((string) $data['sold_at']),
                    (int) $data['sold_price'],
                );

                Notification::make()
                    ->success()
                    ->title($record->title.' marked as sold')
                    ->send();
            })
            ->after(function ($livewire): void {
                // On the edit page the form must show the car as off the site.
                if (method_exists($livewire, 'refreshFormData')) {
                    $livewire->refreshFormData(['is_active']);
                }
            });
    }

    /** Today's date in the store's timezone. */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('marios.timezone'));
    }

    /**
     * The instant stored for a sale date: right now when the date is today,
     * otherwise noon of that day in the store's timezone (so the sale always
     * lands on the chosen day, whatever the viewer's timezone).
     */
    public static function instantFor(string $date): CarbonImmutable
    {
        $day = CarbonImmutable::parse($date, config('marios.timezone'))->startOfDay();

        if ($day->isSameDay(static::today())) {
            return CarbonImmutable::now();
        }

        return $day->setTime(12, 0);
    }
}
