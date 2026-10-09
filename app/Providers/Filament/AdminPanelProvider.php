<?php

namespace App\Providers\Filament;

use App\Enums\Permission;
use App\Filament\Auth\AppAuthentication;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\DeliveryDashboard;
use App\Filament\Pages\Finances;
use App\Filament\Pages\Maintenance;
use App\Filament\Pages\Settings;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Http\Middleware\EnsureTwoFactorForSensitiveRoles;
use App\Services\Orders\SalesFigures;
use Filament\Actions\Action;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
    public function register(): void
    {
        parent::register();

        // Local testing aid: show the current code of the local test secret (.env) on every 6-digit code field.
        if ($this->app->isLocal()) {
            OneTimeCodeInput::configureUsing(fn (OneTimeCodeInput $input) => $input
                ->hint(fn () => ($code = app(AppAuthentication::class)->getCurrentTestCode()) ? 'Code de test (local) : '.$code : null)
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
            ->userMenuItems(self::quickActions())
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

    /**
     * Shortcuts of the user menu (avatar, top right), in two groups under "Profil": everyday work, then the store's
     * settings. Each one shows only to those allowed to open it.
     *
     * @return list<array<string, Action>>
     */
    private static function quickActions(): array
    {
        $can = fn (Permission $permission): bool => (bool) auth()->user()?->can($permission->value);

        return [
            [
                'storefront' => Action::make('storefront')
                    ->label('Voir la boutique')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => route('home'), shouldOpenInNewTab: true),
                'orders' => Action::make('orders')
                    ->label('Commandes à traiter')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->badge(fn (): ?int => app(SalesFigures::class)->toHandle() ?: null)
                    ->badgeColor('warning')
                    ->url(fn (): string => OrderResource::getUrl('index', ['tab' => 'to_handle']))
                    ->visible(fn (): bool => $can(Permission::ViewOrders)),
                'product' => Action::make('product')
                    ->label('Ajouter un produit')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => ProductResource::getUrl('create'))
                    ->visible(fn (): bool => $can(Permission::ManageCatalog)),
                'delivery' => Action::make('delivery')
                    ->label('Livraisons')
                    ->icon(Heroicon::OutlinedTruck)
                    ->url(fn (): string => DeliveryDashboard::getUrl())
                    ->visible(fn (): bool => DeliveryDashboard::canAccess()),
                'finances' => Action::make('finances')
                    ->label('Finances')
                    ->icon(Heroicon::OutlinedChartBarSquare)
                    ->url(fn (): string => Finances::getUrl())
                    ->visible(fn (): bool => Finances::canAccess()),
                'campaign' => Action::make('campaign')
                    ->label('Nouvelle campagne e-mail')
                    ->icon(Heroicon::OutlinedMegaphone)
                    ->url(fn (): string => NewsletterCampaignResource::getUrl('create'))
                    ->visible(fn (): bool => $can(Permission::ManagePromotions)),
            ],
            [
                'settings' => Action::make('settings')
                    ->label('Paramètres de la boutique')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->url(fn (): string => Settings::getUrl())
                    ->visible(fn (): bool => Settings::canAccess()),
                'maintenance' => Action::make('maintenance')
                    ->label('Maintenance')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->badge(fn (): ?string => Maintenance::getNavigationBadge())
                    ->badgeColor('danger')
                    ->url(fn (): string => Maintenance::getUrl())
                    ->visible(fn (): bool => Maintenance::canAccess()),
            ],
        ];
    }
}
