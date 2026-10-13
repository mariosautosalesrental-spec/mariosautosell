<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Categories are the makes shown as "Shop by make" buttons on the site, in
 * the order set here.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) Category::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('e.g. Toyota')
                    ->required()
                    ->maxLength(255)
                    ->rule(fn (?Category $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        // The mockup treats "jeep" and "Jeep" as the same make.
                        $exists = Category::query()
                            ->whereRaw('lower(name) = ?', [mb_strtolower(trim((string) $value))])
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($exists) {
                            $fail('"'.trim((string) $value).'" already exists.');
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('cars_count')
                    ->label('Cars')
                    ->counts('cars')
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('moveUp')
                    ->label('Move up')
                    ->icon(Heroicon::ArrowUp)
                    ->iconButton()
                    ->disabled(fn (Category $record): bool => $record->isFirst())
                    ->action(fn (Category $record) => $record->moveUp()),
                Action::make('moveDown')
                    ->label('Move down')
                    ->icon(Heroicon::ArrowDown)
                    ->iconButton()
                    ->disabled(fn (Category $record): bool => $record->isLast())
                    ->action(fn (Category $record) => $record->moveDown()),
                EditAction::make()
                    ->modalHeading('Rename category')
                    ->successNotificationTitle('Category renamed'),
                DeleteAction::make()
                    ->modalHeading('Delete category?')
                    ->modalDescription('This removes the category from the "Shop by make" buttons.')
                    ->successNotificationTitle('Category deleted')
                    ->before(function (DeleteAction $action, Category $record): void {
                        $cars = $record->cars()->count();

                        if ($cars > 0) {
                            Notification::make()
                                ->danger()
                                ->title('This category still has cars')
                                ->body(sprintf('"%s" has %d %s (on the site, hidden or sold). Move or delete them first.', $record->name, $cars, $cars === 1 ? 'car' : 'cars'))
                                ->send();

                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
