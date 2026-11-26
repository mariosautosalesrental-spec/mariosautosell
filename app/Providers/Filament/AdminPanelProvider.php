<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->profile()
            // The reset page is how an invited person chooses a password (the link comes from the Users page).
            ->passwordReset()
            ->brandName("Mario's Auto Sales & Rentals")
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3.25rem')
            ->favicon(asset('images/logo.png'))
            ->darkMode(false)
            ->globalSearch(false)
            ->navigationItems([
                NavigationItem::make('View website')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->sort(900),
            ])
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => view('filament.tab-bar')->render())
            ->font('Barlow', provider: GoogleFontProvider::class)
            ->colors([
                // The brand red (#B3121F) is shade 600, which buttons and links use.
                'primary' => [
                    50 => '#FBE9EA',
                    100 => '#F6D0D3',
                    200 => '#EDA3A8',
                    300 => '#E3767D',
                    400 => '#D44853',
                    500 => '#C42431',
                    600 => '#B3121F',
                    700 => '#960F1A',
                    800 => '#7A0C15',
                    900 => '#5F0A11',
                    950 => '#3A060A',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Dashboard widgets are discovered from app/Filament/Widgets.
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
