<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\Support\StorefrontImage;
use App\Models\Setting;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Payments\OnlinePayments;
use App\Services\Storefront\ConfigOverrides;
use App\Services\Storefront\StoreSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Store identity edited without a deployment (F-004, F-111). Empty fields fall back to config/storefront.php.
 */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Paramètres de la boutique';

    protected static ?string $title = 'Paramètres de la boutique';

    protected static ?int $navigationSort = 1;

    /** Audience measurement identifiers (F-155), read by App\Services\Storefront\Analytics. */
    private const ANALYTICS_FIELDS = ['ga4_id', 'meta_pixel_id', 'tiktok_pixel_id', 'search_console_token'];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageSettings->value);
    }

    public function mount(): void
    {
        $settings = app(StoreSettings::class);

        $this->form->fill([
            'contact' => $settings->contact(),
            'social' => $settings->socialLinks()->pluck('url', 'key')->all(),
            'delivery' => [
                'free_shipping_threshold' => Setting::get('delivery.free_shipping_threshold'),
                'assignment_mode' => app(DeliveryDispatcher::class)->mode(),
            ],
            'payment' => [
                'cash_on_delivery_limit' => Setting::get('payment.cash_on_delivery_limit'),
                'online_timeout_minutes' => Setting::get('payment.online_timeout_minutes'),
            ],
            'analytics' => collect(self::ANALYTICS_FIELDS)->mapWithKeys(fn (string $field) => [$field => Setting::get("analytics.{$field}")])->all(),
            // The values in use (settings laid over config/storefront.php by ConfigOverrides).
            'identity' => [
                'name' => config('storefront.name'),
                'description' => config('storefront.description'),
                'about' => config('storefront.about'),
                'logo' => config('storefront.logo'),
                'favicon' => config('storefront.favicon'),
            ],
            'announcements' => [
                'trending' => config('storefront.announcements.trending'),
                'campaign' => config('storefront.announcements.campaign'),
            ],
            'search' => [
                'popular' => config('storefront.search.popular'),
                'placeholders' => config('storefront.search.placeholders'),
            ],
            'product_card' => [
                'shipping_delay' => config('storefront.shipping.delay'),
                'limited_stock_threshold' => config('storefront.product_card.limited_stock_threshold'),
            ],
            'footer' => ['banner' => config('storefront.footer_banner')],
            'apps' => collect(config('storefront.app_stores'))
                ->mapWithKeys(fn (array $store) => [str($store['label'])->slug()->value() => $store['url'] === '#' ? null : $store['url']])
                ->all(),
            'payment_logo' => collect(ConfigOverrides::PAYMENT_LOGOS)
                ->mapWithKeys(fn (string $key, int $index) => [$key => config("storefront.payment_methods.{$index}.logo")])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identité')
                    ->description('Nom et textes de présentation de la boutique, repris sur tout le site, dans Google et dans les SMS.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('identity.name')->label('Nom de la boutique')->maxLength(60),
                        TextInput::make('identity.description')
                            ->label('Description pour Google')
                            ->helperText('Utilisée quand une page n’a pas sa propre description.')
                            ->maxLength(160),
                        Textarea::make('identity.about')->label('Texte « À propos » du pied de page')->rows(2)->maxLength(300)->columnSpanFull(),
                        StorefrontImage::make('identity.logo', 'branding')->label('Logo'),
                        StorefrontImage::make('identity.favicon', 'branding')->label('Icône d’onglet (favicon)')->helperText('Image carrée, 64 × 64 px ou plus.'),
                    ]),
                Section::make('Messages et recherche')
                    ->description('Tapez une phrase puis Entrée pour l’ajouter ; la croix la retire.')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('announcements.trending')->label('Messages défilants du bandeau du haut')->placeholder('Nouveau message'),
                        TagsInput::make('announcements.campaign')->label('Messages défilants de l’en-tête (au défilement)')->placeholder('Nouveau message'),
                        TagsInput::make('search.popular')->label('Recherches populaires')->helperText('Chaque mot lance la recherche correspondante.')->placeholder('Nouveau mot'),
                        TagsInput::make('search.placeholders')->label('Textes d’exemple du champ de recherche')->placeholder('Nouveau texte'),
                    ]),
                Section::make('Fiches produit')
                    ->columns(2)
                    ->schema([
                        TextInput::make('product_card.shipping_delay')->label('Délai de livraison affiché')->placeholder('Livraison à Abidjan en 24 à 48 h')->maxLength(120),
                        TextInput::make('product_card.limited_stock_threshold')
                            ->label('« Stock limité » à partir de')
                            ->helperText('Nombre d’articles restants sous lequel le produit est signalé ; une variante peut avoir son propre seuil.')
                            ->integer()
                            ->minValue(1)
                            ->suffix('articles'),
                    ]),
                Section::make('Pied de page')
                    ->columns(2)
                    ->schema([
                        StorefrontImage::make('footer.banner', 'branding')->label('Bannière du pied de page')->columnSpanFull(),
                        TextInput::make('apps.app-store')->label('Lien App Store')->url()->helperText('Vide : bouton masqué jusqu’à la sortie de l’application.')->maxLength(255),
                        TextInput::make('apps.google-play')->label('Lien Google Play')->url()->maxLength(255),
                        StorefrontImage::make('payment_logo.orange-money', 'payment')->label('Logo Orange Money'),
                        StorefrontImage::make('payment_logo.mtn-momo', 'payment')->label('Logo MTN MoMo'),
                        StorefrontImage::make('payment_logo.moov-money', 'payment')->label('Logo Moov Money'),
                        StorefrontImage::make('payment_logo.wave', 'payment')->label('Logo Wave'),
                        StorefrontImage::make('payment_logo.cash-on-delivery', 'payment')->label('Logo « Paiement à la livraison »')->helperText('Sans logo, le nom du moyen de paiement est affiché.'),
                    ]),
                Section::make('Coordonnées')
                    ->description('Affichées dans l’en-tête, le pied de page et le menu mobile.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact.phone')->label('Téléphone')->tel()->maxLength(30),
                        TextInput::make('contact.whatsapp')
                            ->label('Numéro WhatsApp')
                            ->helperText('Format international, par exemple 2250700000000.')
                            ->regex('/^\d{8,15}$/')
                            ->maxLength(15),
                        TextInput::make('contact.email')->label('E-mail de contact')->email()->maxLength(255),
                        TextInput::make('contact.opening_hours')->label('Horaires')->maxLength(255),
                        TextInput::make('contact.address')->label('Adresse')->maxLength(255)->columnSpanFull(),
                    ]),
                Section::make('Livraison')
                    ->schema([
                        TextInput::make('delivery.free_shipping_threshold')
                            ->label('Livraison offerte à partir de')
                            ->helperText('Montant du panier (hors livraison). Vide : pas de livraison offerte.')
                            ->integer()
                            ->minValue(0)
                            ->suffix('FCFA'),
                        Select::make('delivery.assignment_mode')
                            ->label('Attribution des livraisons')
                            ->helperText('À la confirmation d’une commande.')
                            ->options([
                                DeliveryDispatcher::FIRST_TO_ACCEPT => 'Le premier livreur de la zone qui accepte',
                                DeliveryDispatcher::AUTOMATIC => 'Automatique : le livreur de la zone le moins chargé',
                            ])
                            ->required(),
                    ]),
                Section::make('Paiement')
                    ->schema([
                        TextInput::make('payment.cash_on_delivery_limit')
                            ->label('Paiement à la livraison jusqu’à')
                            ->helperText('Montant total maximum d’une commande payée à la livraison. Vide : pas de plafond.')
                            ->integer()
                            ->minValue(0)
                            ->suffix('FCFA'),
                        TextInput::make('payment.online_timeout_minutes')
                            ->label('Annuler une commande en ligne non payée après')
                            ->helperText('Son stock est alors remis en vente. Vide : '.OnlinePayments::DEFAULT_TIMEOUT.' minutes (10 au minimum).')
                            ->integer()
                            ->minValue(10)
                            ->maxValue(1440)
                            ->suffix('minutes'),
                        Placeholder::make('payment.online_state')
                            ->label('Paiement en ligne (CinetPay)')
                            ->content(fn () => app(OnlinePayments::class)->isAvailable()
                                ? 'Actif : proposé au checkout (Orange Money, MTN MoMo, Moov Money, Wave, carte).'
                                : 'Inactif : les clés CINETPAY_API_KEY et CINETPAY_API_PASSWORD du compte marchand ne sont pas encore renseignées sur le serveur.'),
                    ]),
                Section::make('Mesure d’audience')
                    ->description('Chargés seulement après l’accord du visiteur (bandeau cookies). Laisser vide pour ne pas utiliser un service ; sans aucun identifiant, le bandeau n’est pas affiché.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('analytics.ga4_id')->label('Google Analytics 4 (ID de mesure)')->placeholder('G-XXXXXXXXXX')->regex('/^G-[A-Z0-9]{4,20}$/')->maxLength(30),
                        TextInput::make('analytics.meta_pixel_id')->label('Pixel Meta (Facebook, Instagram)')->regex('/^\d{5,20}$/')->maxLength(20),
                        TextInput::make('analytics.tiktok_pixel_id')->label('Pixel TikTok')->regex('/^[A-Z0-9]{10,30}$/')->maxLength(30),
                        TextInput::make('analytics.search_console_token')
                            ->label('Google Search Console (code de validation)')
                            ->helperText('Le contenu de la balise « google-site-verification » proposée par Google.')
                            ->regex('/^[A-Za-z0-9_-]{10,100}$/')
                            ->maxLength(100),
                    ]),
                Section::make('Réseaux sociaux')
                    ->description('Laisser vide pour masquer l’icône du réseau.')
                    ->columns(2)
                    ->schema(collect(config('storefront.social'))
                        ->map(fn (array $network) => TextInput::make("social.{$network['key']}")
                            ->label(ucfirst($network['key']))
                            ->url()
                            ->maxLength(255))
                        ->all()),
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
                        Action::make('save')->label('Enregistrer')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::store([
            ...collect($state['contact'] ?? [])->mapWithKeys(fn ($value, $field) => ["contact.{$field}" => $value])->all(),
            ...collect($state['social'] ?? [])->mapWithKeys(fn ($value, $network) => ["social.{$network}" => $value])->all(),
            'delivery.free_shipping_threshold' => $state['delivery']['free_shipping_threshold'] ?? null,
            'delivery.assignment_mode' => $state['delivery']['assignment_mode'] ?? null,
            'payment.cash_on_delivery_limit' => $state['payment']['cash_on_delivery_limit'] ?? null,
            'payment.online_timeout_minutes' => $state['payment']['online_timeout_minutes'] ?? null,
            ...collect(self::ANALYTICS_FIELDS)->mapWithKeys(fn (string $field) => ["analytics.{$field}" => $state['analytics'][$field] ?? null])->all(),
            'identity.name' => $state['identity']['name'] ?? null,
            'identity.description' => $state['identity']['description'] ?? null,
            'identity.about' => $state['identity']['about'] ?? null,
            'identity.logo' => $state['identity']['logo'] ?? null,
            'identity.favicon' => $state['identity']['favicon'] ?? null,
            'announcements.trending' => self::list($state['announcements']['trending'] ?? []),
            'announcements.campaign' => self::list($state['announcements']['campaign'] ?? []),
            'search.popular' => self::list($state['search']['popular'] ?? []),
            'search.placeholders' => self::list($state['search']['placeholders'] ?? []),
            'product_card.shipping_delay' => $state['product_card']['shipping_delay'] ?? null,
            'product_card.limited_stock_threshold' => $state['product_card']['limited_stock_threshold'] ?? null,
            'footer.banner' => $state['footer']['banner'] ?? null,
            ...collect($state['apps'] ?? [])->mapWithKeys(fn ($url, string $store) => ["apps.{$store}" => $url])->all(),
            ...collect($state['payment_logo'] ?? [])->mapWithKeys(fn ($logo, string $method) => ["payment_logo.{$method}" => $logo])->all(),
        ]);

        activity()->causedBy(auth()->user())->withProperties(['keys' => array_keys($state)])->log('Paramètres de la boutique modifiés');

        Notification::make()->title('Paramètres enregistrés')->success()->send();
    }

    /**
     * A list typed in a tags field, stored as JSON; an empty list keeps the default of config/storefront.php.
     *
     * @param  array<int, string>|null  $items
     */
    private static function list(?array $items): ?string
    {
        $items = array_values(array_filter(array_map('trim', $items ?? []), 'filled'));

        return $items === [] ? null : json_encode($items, JSON_UNESCAPED_UNICODE);
    }
}
