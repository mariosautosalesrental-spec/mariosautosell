<?php

namespace App\Filament\Actions;

use App\Models\Car;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Undoes a sale made by mistake. The car comes back hidden; the store turns
 * it back on when ready.
 */
class MarkAsAvailableAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'markAsAvailable';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Mark as available')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->visible(fn (Car $record): bool => $record->isSold())
            ->requiresConfirmation()
            ->modalHeading(fn (Car $record): string => 'Mark '.$record->title.' as available?')
            ->modalDescription('The sale is removed from the sales numbers. The car stays hidden from the website until you turn on "Show on website".')
            ->modalSubmitActionLabel('Mark as available')
            ->action(function (Car $record): void {
                $record->reopen();

                Notification::make()
                    ->success()
                    ->title($record->title.' is available again')
                    ->body('It is hidden from the website. Turn on "Show on website" when you are ready.')
                    ->send();
            })
            ->after(function ($livewire): void {
                if (method_exists($livewire, 'refreshFormData')) {
                    $livewire->refreshFormData(['is_active']);
                }
            });
    }
}
