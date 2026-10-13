<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * "Store info": the single form that feeds the header, "Visit the lot", the
 * footer, the map and every WhatsApp button on the public site.
 *
 * @property-read Schema $form
 */
class StoreSettings extends Page
{
    protected static ?string $navigationLabel = 'Store info';

    protected static ?string $title = 'Store info';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'store-info';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::current()->only(Setting::FORM_FIELDS));
    }

    public function getSubheading(): ?string
    {
        return 'Shown in the site header, Visit the lot, the footer and the map, and used by every WhatsApp button.';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact')
                ->columns(2)
                ->schema([
                    TextInput::make('whatsapp')
                        ->label('WhatsApp number')
                        ->helperText('Digits only, with the country code (US: 1 + area code + number). Example: 19784174036.')
                        ->required()
                        ->tel()
                        ->regex('/^[1-9][0-9]{7,14}$/')
                        ->validationMessages(['regex' => 'Use digits only, with the country code and no spaces, dashes or +. Example: 19784174036.'])
                        ->maxLength(15)
                        ->inputMode('numeric'),
                    TextInput::make('phone')
                        ->label('Phone')
                        ->helperText('As it is shown on the site. Example: (978) 417-4036.')
                        ->required()
                        ->tel()
                        ->maxLength(255),
                ]),
            Section::make('Hours')
                ->description('Free text. Example: 9:00 AM – 6:00 PM, or Closed.')
                ->columns(3)
                ->schema([
                    TextInput::make('hours_mon_fri')->label('Mon–Fri')->required()->maxLength(255),
                    TextInput::make('hours_sat')->label('Saturday')->required()->maxLength(255),
                    TextInput::make('hours_sun')->label('Sunday')->required()->maxLength(255),
                ]),
            Section::make('Address')
                ->columns(2)
                ->schema([
                    TextInput::make('street')->label('Street')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('city')->label('City')->required()->maxLength(255),
                    TextInput::make('state')->label('State')->required()->maxLength(255),
                    TextInput::make('zip')->label('ZIP')->required()->maxLength(255),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save changes')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $setting = Setting::query()->first() ?? new Setting;
        $setting->fill($data)->save(); // saving clears the cached store info

        Notification::make()
            ->success()
            ->title('Store info saved')
            ->send();
    }
}
