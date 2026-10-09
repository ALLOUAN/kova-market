<?php

namespace App\Providers\Filament;

use App\Filament\Auth\AppAuthentication;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\EnsureTwoFactorForSensitiveRoles;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        // Local testing aid: show the current code of the local test secret on every 6-digit code field.
        if ($this->app->isLocal()) {
            OneTimeCodeInput::configureUsing(fn (OneTimeCodeInput $input) => $input
                ->hint(fn () => 'Code de test (local) : '.app(AppAuthentication::class)->getCurrentTestCode())
                ->hintColor('warning'));
        }
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('admin.path'))
            ->brandName(config('storefront.name'))
            // The logo's colours (night blue, green, gold): public/assets/admin/kova-admin.css.
            ->brandLogo(asset('assets/images/logo/kova-market.webp'))
            ->darkModeBrandLogo(asset('assets/images/logo/kova-market-dark.webp'))
            ->brandLogoHeight('3.25rem')
            ->favicon(asset(config('storefront.favicon')))
            ->login(Login::class)
            ->profile()
            // Authenticator-app codes, mandatory for super-admins and managers (F-100). The requirement is
            // switched on for the panel; the middleware narrows it down to those roles.
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(EnsureTwoFactorForSensitiveRoles::class)
            ->colors([
                // Orange of the logo ("MARKET" and the cart): #F85000 at 400; buttons use 600 (#B83A00) and hover 500 (#CC4400),
                // both AA with white text, otherwise Filament falls back to a pale shade with dark text.
                'primary' => array_map(Color::convertToOklch(...), [
                    50 => '#fff4ec', 100 => '#ffe8da', 200 => '#ffcfb0', 300 => '#ffab7a', 400 => '#f85000', 500 => '#cc4400',
                    600 => '#b83a00', 700 => '#a33400', 800 => '#852b05', 900 => '#6c250a', 950 => '#3a1002',
                ]),
                'gray' => Color::Slate,
            ])
            // Back-office alerts (new and cancelled orders), refreshed every 30 seconds.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '<link rel="stylesheet" href="'.e(asset('assets/admin/kova-admin.css').'?v='.@filemtime(public_path('assets/admin/kova-admin.css'))).'">')
            ->navigationGroups(['Ventes', 'Catalogue', 'Promotions', 'Livraison', 'Contenus', 'Administration'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            // Dashboard: App\Filament\Pages\Dashboard (found with the other pages), widgets from App\Filament\Widgets.
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
