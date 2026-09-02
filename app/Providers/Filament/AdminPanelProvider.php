<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentAsset::register([
            Css::make('admin-theme', resource_path('css/filament/admin-theme.css')),
        ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(null)
            ->darkMode(false)
            ->brandName('ALQIMI Capture Deck')
            ->brandLogo(fn () => view('filament.branding.logo'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                // Brand gold (Pantone 604 C digital match, #E6D11C at 500) —
                // drives buttons, links, and focus rings across the panel.
                'primary' => [
                    50 => '#fefce8',
                    100 => '#fbf6c4',
                    200 => '#f5e985',
                    300 => '#eedb49',
                    400 => '#ead52f',
                    500 => '#e6d11c',
                    600 => '#c2af17',
                    700 => '#998a12',
                    800 => '#6f640d',
                    900 => '#474009',
                    950 => '#2e2906',
                ],
                // Brand grey (Pantone 423 C digital match, #9EA0A3), replacing
                // Filament's default slate. The exact brand value sits at 400
                // rather than 500: Filament's default theme uses the 500/600
                // steps for body text and icons, and #9EA0A3 there read as
                // washed-out/low-contrast against the white sidebar. Steps
                // 500+ are darkened for legibility instead of evenly spaced
                // around the brand value.
                'gray' => [
                    50 => '#f7f7f8',
                    100 => '#eff0f1',
                    200 => '#dee0e2',
                    300 => '#c7c9cc',
                    400 => '#9ea0a3',
                    500 => '#6f7275',
                    600 => '#5a5c5f',
                    700 => '#454749',
                    800 => '#313234',
                    900 => '#202122',
                    950 => '#141516',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
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
