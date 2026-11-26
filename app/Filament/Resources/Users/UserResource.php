<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;

/**
 * The people who can sign in to the admin. New people are invited with a link
 * (the store has no e-mail sending), and the same link resets a forgotten password.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'user';

    public static function getNavigationBadge(): ?string
    {
        return (string) User::count();
    }

    /** A one-time link that lets the person choose a password. Valid for the time set in config/auth.php. */
    public static function accessLink(User $user): string
    {
        return Filament::getResetPasswordUrl(Password::broker()->createToken($user), $user);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->description(fn (User $record): ?string => $record->is(auth()->user()) ? 'You' : null)
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('accessLink')
                    ->label('Link')
                    ->icon(Heroicon::OutlinedLink)
                    ->iconButton()
                    ->tooltip('Invite or reset password link')
                    ->modalHeading(fn (User $record): string => 'Link for '.$record->name)
                    ->modalDescription('Send this link to the person (WhatsApp, for example). It lets them choose a password and is valid for 7 days. A new link replaces the old one. Anyone with the link can set the password, so send it only to them.')
                    ->modalWidth('lg')
                    ->fillForm(fn (User $record): array => ['link' => static::accessLink($record)])
                    ->schema([
                        TextInput::make('link')
                            ->label('Link')
                            ->readOnly()
                            ->copyable(copyMessage: 'Link copied', copyMessageDuration: 2000),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                EditAction::make()
                    ->modalHeading('Edit user')
                    ->successNotificationTitle('User saved'),
                DeleteAction::make()
                    ->modalHeading('Remove this user?')
                    ->modalDescription('They can no longer sign in to the admin. Cars and sales are not affected.')
                    ->successNotificationTitle('User removed')
                    ->hidden(fn (User $record): bool => $record->is(auth()->user()) || User::count() <= 1),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
