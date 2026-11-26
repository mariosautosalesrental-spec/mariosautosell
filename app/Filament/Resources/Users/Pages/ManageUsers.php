<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected ?string $subheading = 'People who can sign in to this admin.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Invite user')
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalHeading('Invite user')
                ->modalDescription('You get a link to send them. They open it and choose their own password.')
                ->modalSubmitActionLabel('Create invite')
                ->modalWidth('md')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'users', column: 'email'),
                ])
                ->action(function (array $data): void {
                    // Nobody knows this password; the person sets their own through the link.
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Str::random(40),
                    ]);

                    $this->mountTableAction('accessLink', (string) $user->getKey());
                }),
        ];
    }
}
