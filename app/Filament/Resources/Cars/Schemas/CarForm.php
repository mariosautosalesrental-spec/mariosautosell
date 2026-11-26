<?php

namespace App\Filament\Resources\Cars\Schemas;

use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Category;
use App\Rules\CanBeActive;
use App\Rules\UniqueCategoryName;
use App\Services\CarPhotoSync;
use App\Services\VinDecoder;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

/**
 * The add/edit car form: photos, details and what shows on the site, with a
 * live preview of the car card beside it.
 */
class CarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->schema([
                        Group::make([
                            self::photosSection(),
                            self::detailsSection(),
                            self::visibilitySection(),
                        ])->columnSpan(['lg' => 2]),

                        Group::make([
                            View::make('filament.cars.preview'),
                        ])->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }

    private static function photosSection(): Section
    {
        return Section::make('Photos')
            ->schema([
                FileUpload::make('photos')
                    ->hiddenLabel()
                    ->helperText('The first photo is the cover on the site. Drag the photos to change the order.')
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(config('marios.max_photo_kb'))
                    ->maxFiles(30)
                    ->disk(config('marios.photos_disk'))
                    ->directory(CarPhotoSync::UPLOAD_DIRECTORY)
                    ->visibility('public')
                    ->panelLayout('grid')
                    ->imagePreviewHeight('120')
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('2000')
                    ->imageResizeTargetHeight('2000')
                    ->imageResizeUpscale(false)
                    ->preventFilePathTampering(
                        true,
                        fn (string $file): bool => str_starts_with($file, CarPhotoSync::UPLOAD_DIRECTORY.'/')
                            || CarPhoto::query()->where('file_path', $file)->exists(),
                    )
                    ->live()
                    ->columnSpanFull(),
            ]);
    }

    private static function detailsSection(): Section
    {
        return Section::make('Details')
            ->columns(['default' => 1, 'sm' => 2])
            ->schema([
                self::vinField(),
                Hidden::make('vin_data'),
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name', fn (Builder $query) => $query->ordered())
                    ->required()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder('e.g. Toyota')
                            ->required()
                            ->maxLength(255)
                            ->rule(new UniqueCategoryName),
                    ])
                    ->createOptionModalHeading('Add category')
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->label('Name')
                    ->placeholder('2019 Jeep Compass')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true),
                TextInput::make('trim')
                    ->label('Version')
                    ->placeholder('Limited 4x4')
                    ->maxLength(255)
                    ->live(onBlur: true),
                TextInput::make('price')
                    ->label('Price')
                    ->helperText('Includes all dealer fees. Whole dollars.')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(9999999)
                    ->prefix('$')
                    ->live(onBlur: true),
                TextInput::make('mileage')
                    ->label('Mileage')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(999999)
                    ->suffix('mi'),
                Select::make('body_type_id')
                    ->label('Body type')
                    ->relationship('bodyType', 'name', fn (Builder $query) => $query->ordered())
                    ->required()
                    ->preload(),
                Select::make('transmission_id')
                    ->label('Transmission')
                    ->relationship('transmission', 'name', fn (Builder $query) => $query->ordered())
                    ->required()
                    ->preload(),
                Select::make('drivetrain_id')
                    ->label('Drivetrain')
                    ->relationship('drivetrain', 'name', fn (Builder $query) => $query->ordered())
                    ->required()
                    ->preload(),
                TextInput::make('color')
                    ->label('Color')
                    ->maxLength(255),
                TextInput::make('engine')
                    ->label('Engine (optional)')
                    ->placeholder('2.4L 4-cyl')
                    ->helperText('Only used when the fields below are empty.')
                    ->maxLength(255),
                TextInput::make('year')->label('Year')->numeric()->integer()->minValue(1950)->maxValue((int) date('Y') + 2),
                TextInput::make('doors')->label('Doors')->numeric()->integer()->minValue(1)->maxValue(8),
                TextInput::make('seats')->label('Seats')->numeric()->integer()->minValue(1)->maxValue(15),
                TextInput::make('cylinders')->label('Cylinders')->numeric()->integer()->minValue(1)->maxValue(16),
                TextInput::make('displacement_l')->label('Engine size')->numeric()->minValue(0.1)->maxValue(9.9)->step(0.1)->suffix('L'),
                TextInput::make('horsepower')->label('Horsepower')->numeric()->integer()->minValue(1)->maxValue(2000)->suffix('hp'),
                TextInput::make('fuel_type')->label('Fuel')->placeholder('Gasoline')->maxLength(255),
                TextInput::make('airbags')->label('Airbags')->placeholder('Front, Side')->maxLength(255),
                TextInput::make('tpms')->label('Tire pressure monitor')->placeholder('Direct')->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->helperText('A few lines.')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * The VIN box: "Look up" fills the form with what the VIN says about the car.
     * Everything stays editable and the form works the same without it.
     */
    private static function vinField(): TextInput
    {
        return TextInput::make('vin')
            ->label('VIN')
            ->placeholder('17 characters')
            ->helperText('Type the VIN and press Look up to fill in the car details.')
            ->maxLength(17)
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? VinDecoder::normalize($state) : null)
            ->rules([
                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                    if (filled($value) && ! VinDecoder::isValid((string) $value)) {
                        $fail('A VIN has 17 letters and numbers (no I, O or Q).');
                    }
                },
            ])
            ->unique(table: 'cars', column: 'vin', ignoreRecord: true)
            ->columnSpanFull()
            ->suffixAction(
                Action::make('lookupVin')
                    ->label('Look up')
                    ->icon('heroicon-m-magnifying-glass')
                    ->action(function (Get $get, Set $set, ?Car $record): void {
                        $vin = (string) $get('vin');

                        if (! VinDecoder::isValid($vin)) {
                            Notification::make()->warning()->title('Enter a valid 17-character VIN first.')->send();

                            return;
                        }

                        if (Car::query()->where('vin', VinDecoder::normalize($vin))->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))->exists()) {
                            Notification::make()->warning()->title('A car with this VIN is already in the lot.')->send();

                            return;
                        }

                        $data = app(VinDecoder::class)->decode($vin);

                        if ($data === null) {
                            Notification::make()->warning()
                                ->title('Could not read this VIN')
                                ->body('Check the number or fill in the details by hand.')
                                ->send();

                            return;
                        }

                        $category = Category::query()->whereRaw('lower(name) = ?', [mb_strtolower($data['make'])])->first()
                            ?? Category::query()->create(['name' => $data['make']]);

                        $set('vin', $data['vin']);
                        $set('vin_data', $data['vin_data']);
                        $set('category_id', $category->getKey());

                        foreach (['title', 'trim', 'year', 'doors', 'seats', 'cylinders', 'displacement_l', 'horsepower', 'fuel_type', 'airbags', 'tpms', 'body_type_id', 'transmission_id', 'drivetrain_id'] as $field) {
                            // Never wipe what is already there with a blank answer.
                            if ($data[$field] !== null) {
                                $set($field, $data[$field]);
                            }
                        }

                        Notification::make()->success()
                            ->title($data['title'])
                            ->body($data['complete'] ? 'Filled in from the VIN. You can edit anything.' : 'Partly filled in: please check the details.')
                            ->send();
                    }),
            );
    }

    private static function visibilitySection(): Section
    {
        return Section::make('On the site')
            ->schema([
                Toggle::make('is_active')
                    ->label('Show on website')
                    ->onColor('success')
                    ->default(true)
                    ->disabled(fn (?Car $record): bool => (bool) $record?->isSold())
                    ->dehydrated(fn (?Car $record): bool => ! $record?->isSold())
                    ->helperText(fn (?Car $record): ?string => $record?->isSold()
                        ? 'This car is sold. Mark it as available to put it back on the site.'
                        : 'A car needs a price and at least one photo to be on the site. Turn this off to save a draft.')
                    ->rules([
                        fn (Get $get, ?Car $record): ValidationRule => new CanBeActive(
                            price: filled($get('price')) ? (int) $get('price') : null,
                            photoCount: count((array) $get('photos')),
                            isSold: (bool) $record?->isSold(),
                        ),
                    ]),
                Toggle::make('price_drop')
                    ->label('"Price drop" badge')
                    ->helperText('Shows a Price drop badge on the car card.')
                    ->default(false)
                    ->live(),
            ]);
    }
}
