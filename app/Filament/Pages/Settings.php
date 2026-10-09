<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\Settings\Widgets\SettingsOverview;
use App\Filament\Support\StorefrontImage;
use App\Models\Category;
use App\Models\Setting;
use App\Notifications\Channels\Messaging;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Orders\WeighIn;
use App\Services\Payments\OnlinePayments;
use App\Services\Storefront\Analytics;
use App\Services\Storefront\ConfigOverrides;
use App\Services\Storefront\HomePageService;
use App\Services\Storefront\Market;
use App\Services\Storefront\ProductViewers;
use App\Services\Storefront\StoreSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Store identity edited without a deployment (F-004, F-111). Empty fields fall back to config/storefront.php.
 */
class Settings extends Page
{
    /** How the payment logos show in the footer: 28 px high, drawn at twice that size for sharp screens. */
    private const PAYMENT_LOGO = 'affiché sur 28 px de haut, largeur libre, fond transparent';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Paramètres de la boutique';

    protected static ?string $title = 'Paramètres de la boutique';

    /**
     * The header band (SettingsOverview) carries the title, the explanation and what is set up or missing.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [SettingsOverview::class];
    }

    protected static ?int $navigationSort = 1;

    /** Audience measurement identifiers (F-155), read by App\Services\Storefront\Analytics. */
    private const ANALYTICS_FIELDS = ['ga4_id', 'meta_pixel_id', 'tiktok_pixel_id', 'search_console_token'];

    /** Icons offered for the home page guarantees (Font Awesome names). */
    private const GUARANTEE_ICONS = [
        'truck-fast' => 'Livraison',
        'mobile-screen' => 'Mobile Money',
        'hand-holding-dollar' => 'Paiement à la livraison',
        'credit-card' => 'Carte bancaire',
        'rotate-left' => 'Retours',
        'shield-check' => 'Sécurité, garantie',
        'headset' => 'Service client',
        'award' => 'Qualité, authenticité',
        'tags' => 'Prix, promotions',
        'circle-check' => 'Autre',
    ];

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
            'orders' => ['weigh_tolerance' => Setting::get('orders.weigh_tolerance')],
            'notifications' => [
                'whatsapp' => Messaging::whatsappEnabled(),
                'sms' => Messaging::smsEnabled(),
            ],
            'whatsapp_content' => collect(config('whatsapp.templates'))->mapWithKeys(fn (array $template, string $key) => [$key => Setting::get("whatsapp.twilio_content.{$key}")])->all(),
            'cookies' => [
                'always' => Setting::get('cookies.always', '1') !== '0',
                ...collect(array_keys(Analytics::BANNER_TEXTS))->mapWithKeys(fn (string $key) => [$key => Setting::get("cookies.{$key}")])->all(),
            ],
            'market' => [
                'enabled' => Setting::get('market.enabled', '1') !== '0',
                'in_menu' => Setting::get('market.in_menu', '1') !== '0',
                'category_id' => Setting::get('market.category_id'),
                'per_row' => Setting::get('market.per_row'),
                'title' => Setting::get('market.title'),
                'subtitle' => Setting::get('market.subtitle'),
                'banner' => Setting::get('market.banner'),
            ],
            'analytics' => collect(self::ANALYTICS_FIELDS)->mapWithKeys(fn (string $field) => [$field => Setting::get("analytics.{$field}")])->all(),
            // The values in use (settings laid over config/storefront.php by ConfigOverrides).
            'identity' => [
                'name' => config('storefront.name'),
                'description' => config('storefront.description'),
                'about' => config('storefront.about'),
                'logo' => config('storefront.logo'),
                'favicon' => config('storefront.favicon'),
                'home_title' => config('storefront.home_title'),
            ],
            'newsletter' => [
                'footer' => (bool) config('storefront.features.newsletter'),
                'popup' => (bool) config('storefront.features.welcome_popup'),
                ...collect(ConfigOverrides::NEWSLETTER_TEXTS)->mapWithKeys(fn (string $field) => [$field => config("storefront.newsletter.{$field}")])->all(),
            ],
            'home' => [
                'guarantees' => array_values(config('storefront.guarantees')),
                'sections' => HomePageService::sectionSettings(),
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
                'viewers_enabled' => ProductViewers::enabled(),
                'viewers_minimum' => ProductViewers::minimum(),
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
                // One tab per theme; the chosen tab stays in the address (?tab=…) after saving or reloading.
                Tabs::make('Paramètres')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Boutique')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->schema([
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
                                        StorefrontImage::make('identity.logo', 'branding', [1487, 334], 'logo horizontal, fond transparent')->label('Logo'),
                                        StorefrontImage::make('identity.favicon', 'branding', [64, 64], 'image carrée, jusqu’à 512 × 512 px')->label('Icône d’onglet (favicon)'),
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
                                Section::make('Réseaux sociaux')
                                    ->description('Laisser vide pour masquer l’icône du réseau.')
                                    ->columns(2)
                                    ->schema(collect(config('storefront.social'))
                                        ->map(fn (array $network) => TextInput::make("social.{$network['key']}")
                                            ->label(ucfirst($network['key']))
                                            ->url()
                                            ->maxLength(255))
                                        ->all()),
                            ]),
                        Tab::make('Accueil')
                            ->icon(Heroicon::OutlinedHome)
                            ->schema([
                                Section::make('Titre dans Google')
                                    ->description('Le titre de la page d’accueil dans les résultats Google et l’onglet du navigateur.')
                                    ->schema([
                                        TextInput::make('identity.home_title')
                                            ->label('Titre de la page d’accueil')
                                            ->placeholder(config('storefront.name').' - Boutique en ligne à Abidjan')
                                            ->helperText('Vide : le nom de la boutique suivi de « Boutique en ligne à Abidjan ». 60 à 70 caractères au plus pour être lu en entier dans Google.')
                                            ->maxLength(70),
                                    ]),
                                Section::make('Bandeau de garanties')
                                    ->description('Affiché sous le carrousel : ce qui rassure avant d’acheter (livraison, paiement, service client…).')
                                    ->schema([
                                        Repeater::make('home.guarantees')
                                            ->hiddenLabel()
                                            ->helperText('4 garanties au plus. Retirez-les toutes pour masquer le bandeau.')
                                            ->schema([
                                                Select::make('icon')
                                                    ->label('Icône')
                                                    ->options(self::GUARANTEE_ICONS)
                                                    ->default('circle-check')
                                                    ->required(),
                                                TextInput::make('title')->label('Titre')->required()->maxLength(40),
                                                TextInput::make('text')->label('Précision')->maxLength(60),
                                            ])
                                            ->table([
                                                TableColumn::make('Icône')->width('30%'),
                                                TableColumn::make('Titre')->markAsRequired(),
                                                TableColumn::make('Précision'),
                                            ])
                                            ->maxItems(4)
                                            ->reorderable()
                                            ->addActionLabel('Ajouter une garantie')
                                            ->defaultItems(0),
                                    ]),
                                Section::make('Sections de la page')
                                    ->description('Glissez les sections pour changer leur ordre ; décochez « Affichée » pour en masquer une. Une section sans contenu (aucune bannière, aucun produit) reste masquée de toute façon.')
                                    ->schema([
                                        Repeater::make('home.sections')
                                            ->hiddenLabel()
                                            ->schema([
                                                Hidden::make('key'),
                                                Toggle::make('visible')->label('Affichée')->default(true),
                                            ])
                                            ->itemLabel(fn (array $state): ?string => HomePageService::SECTIONS[$state['key'] ?? ''] ?? null)
                                            ->reorderable()
                                            ->reorderableWithDragAndDrop()
                                            ->addable(false)
                                            ->deletable(false)
                                            ->collapsible(false)
                                            ->grid(['default' => 1, 'md' => 2, 'xl' => 3]),
                                    ]),
                            ]),
                        Tab::make('En-tête')
                            ->icon(Heroicon::OutlinedMegaphone)
                            ->schema([
                                Section::make('Messages et recherche')
                                    ->description('Tapez une phrase puis Entrée pour l’ajouter ; la croix la retire.')
                                    ->columns(2)
                                    ->schema([
                                        TagsInput::make('announcements.trending')->label('Messages défilants du bandeau du haut')->placeholder('Nouveau message'),
                                        TagsInput::make('announcements.campaign')->label('Messages défilants de l’en-tête (au défilement)')->placeholder('Nouveau message'),
                                        TagsInput::make('search.popular')->label('Recherches populaires')->helperText('Chaque mot lance la recherche correspondante.')->placeholder('Nouveau mot'),
                                        TagsInput::make('search.placeholders')->label('Textes d’exemple du champ de recherche')->placeholder('Nouveau texte'),
                                    ]),
                            ]),
                        Tab::make('Produits')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->schema([
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
                                        Toggle::make('product_card.viewers_enabled')
                                            ->label('Badge « N personnes ont vu ce produit »')
                                            ->helperText('Visites réelles de la fiche produit ces '.ProductViewers::WINDOW_MINUTES.' dernières minutes, une par visiteur. Mis à jour chaque minute.'),
                                        TextInput::make('product_card.viewers_minimum')
                                            ->label('Affiché à partir de')
                                            ->helperText('En dessous, aucun badge : un « 1 personne » n’incite personne.')
                                            ->integer()
                                            ->minValue(2)
                                            ->maxValue(100)
                                            ->suffix('visiteurs'),
                                    ]),
                            ]),
                        Tab::make('Mon Marché')
                            ->icon(Heroicon::OutlinedShoppingCart)
                            ->schema([
                                Section::make('Page « Mon Marché »')
                                    ->description('La vitrine du marché (/mon-marche) : bannière, rayons en raccourcis et une rangée de produits par rayon. Le rayon choisi y mène aussi depuis les menus.')
                                    ->columns(2)
                                    ->headerActions([
                                        Action::make('openMarket')
                                            ->label('Voir la page')
                                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                            ->color('gray')
                                            ->url(fn () => route('market.show'), shouldOpenInNewTab: true)
                                            ->visible(fn () => app(Market::class)->enabled()),
                                    ])
                                    ->schema([
                                        Toggle::make('market.enabled')->label('Page en ligne'),
                                        Toggle::make('market.in_menu')->label('Lien « Mon Marché » dans la barre de navigation'),
                                        Select::make('market.category_id')
                                            ->label('Rayon présenté')
                                            ->options(fn () => Category::roots()->pluck('name', 'id'))
                                            ->placeholder('Mon Marché (par défaut)')
                                            ->helperText('Ses catégories deviennent les rayons de la page, avec leurs produits et ceux de leurs sous-catégories.'),
                                        TextInput::make('market.per_row')
                                            ->label('Produits par rayon')
                                            ->integer()
                                            ->minValue(4)
                                            ->maxValue(12)
                                            ->placeholder((string) Market::DEFAULT_PER_ROW)
                                            ->helperText('De 4 à 12 ; le lien « Tout voir » mène au rayon complet.'),
                                        TextInput::make('market.title')
                                            ->label('Titre')
                                            ->placeholder('Mon Marché')
                                            ->helperText('Aussi le libellé du lien de la barre de navigation. Vide : le nom du rayon.')
                                            ->maxLength(60),
                                        Textarea::make('market.subtitle')
                                            ->label('Texte sous le titre')
                                            ->placeholder('Fruits, légumes, viandes, épicerie et boissons, livrés chez vous.')
                                            ->rows(2)
                                            ->maxLength(200),
                                        StorefrontImage::make('market.banner', 'market', [1920, 420], 'photo de marché ; assombrie pour que le texte blanc reste lisible. Vide : fond bleu nuit')
                                            ->label('Image de la bannière')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Commandes')
                            ->icon(Heroicon::OutlinedTruck)
                            ->schema([
                                Section::make('Livraison')
                                    ->columns(2)
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
                                    ->columns(2)
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
                                        Placeholder::make('payment.online_state')->columnSpanFull()
                                            ->label('Paiement en ligne (CinetPay)')
                                            ->content(fn () => app(OnlinePayments::class)->isAvailable()
                                                ? 'Actif : proposé au checkout (Orange Money, MTN MoMo, Moov Money, Wave, carte).'
                                                : 'Inactif : les clés CINETPAY_API_KEY et CINETPAY_API_PASSWORD du compte marchand ne sont pas encore renseignées sur le serveur.'),
                                    ]),
                                Section::make('Notifications aux clients et aux livreurs')
                                    ->description('Confirmations de commande, suivi, codes de vérification et messages aux livreurs. L’e-mail part toujours quand le client en a donné un.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('notifications.whatsapp')
                                            ->label('WhatsApp (API WhatsApp Business de Meta)')
                                            ->helperText('Messages automatiques au numéro de téléphone, avec les modèles validés par Meta.'),
                                        Toggle::make('notifications.sms')
                                            ->label('SMS')
                                            ->helperText('À laisser désactivé tant qu’aucun fournisseur SMS n’est branché.'),
                                        Placeholder::make('notifications.whatsapp_state')
                                            ->label('État de WhatsApp')
                                            ->content(fn () => match (config('services.whatsapp.driver')) {
                                                'twilio' => filled(config('services.whatsapp.twilio.sid')) && filled(config('services.whatsapp.twilio.token')) && filled(config('services.whatsapp.twilio.from'))
                                                    ? 'Connecté à Twilio (expéditeur '.config('services.whatsapp.twilio.from').') : les messages partent réellement.'
                                                    : 'Clés Twilio manquantes : renseigner TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN et TWILIO_WHATSAPP_FROM sur le serveur.',
                                                'cloud' => filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'))
                                                    ? 'Connecté à Meta : les messages partent réellement.'
                                                    : 'Clés manquantes : renseigner WHATSAPP_TOKEN et WHATSAPP_PHONE_NUMBER_ID sur le serveur.',
                                                default => 'Mode test : les messages sont écrits dans le journal du serveur, rien n’est envoyé.',
                                            })
                                            ->columnSpanFull(),
                                    ]),
                                // One approved template per message. With Twilio, each one's Content SID (HX…) from the
                                // Content Template Builder; without it the message goes as plain text (sandbox, 24 h window).
                                Section::make('Modèles de messages WhatsApp')
                                    ->description('À créer en français avec exactement ce texte, puis à faire valider pour WhatsApp : dans Twilio (Messaging › Content Template Builder) ou dans WhatsApp Manager de Meta. Avec Twilio, collez ici l’identifiant de chaque modèle validé ; sans identifiant, le message part en texte libre, accepté seulement dans le bac à sable ou dans les 24 h qui suivent un message du client.')
                                    ->collapsible()
                                    ->schema(collect(config('whatsapp.templates'))->map(fn (array $template, string $key) => TextInput::make("whatsapp_content.{$key}")
                                        ->label($template['name'].' · '.($template['category'] === 'AUTHENTICATION' ? 'authentification' : 'utilitaire'))
                                        ->placeholder('Content SID Twilio (HX…)')
                                        ->regex('/^HX[0-9a-fA-F]{32}$/')
                                        ->helperText($template['body']))
                                        ->values()->all()),
                                Section::make('Pesée des produits au poids')
                                    ->description('Mon Marché : à la préparation, « Peser les articles » facture la quantité réellement pesée des produits vendus au kg ou au litre (commandes payées à la livraison, avant l’expédition).')
                                    ->schema([
                                        TextInput::make('orders.weigh_tolerance')
                                            ->label('Écart accepté avec la quantité commandée')
                                            ->helperText('En plus ou en moins : à 10 %, 1,5 kg commandés peuvent être pesés de 1,35 à 1,65 kg. 0 : pas de pesée.')
                                            ->integer()
                                            ->minValue(0)
                                            ->maxValue(50)
                                            ->placeholder((string) WeighIn::DEFAULT_TOLERANCE)
                                            ->suffix('%'),
                                    ]),
                            ]),
                        Tab::make('Pied de page')
                            ->icon(Heroicon::OutlinedRectangleGroup)
                            ->schema([
                                Section::make('Pied de page')
                                    ->columns(2)
                                    ->schema([
                                        StorefrontImage::make('footer.banner', 'branding', [1320, 140])->label('Bannière du pied de page')->columnSpanFull(),
                                        TextInput::make('apps.app-store')->label('Lien App Store')->url()->helperText('Vide : bouton masqué jusqu’à la sortie de l’application.')->maxLength(255),
                                        TextInput::make('apps.google-play')->label('Lien Google Play')->url()->maxLength(255),
                                        StorefrontImage::make('payment_logo.orange-money', 'payment', [120, 56], self::PAYMENT_LOGO)->label('Logo Orange Money'),
                                        StorefrontImage::make('payment_logo.mtn-momo', 'payment', [120, 56], self::PAYMENT_LOGO)->label('Logo MTN MoMo'),
                                        StorefrontImage::make('payment_logo.moov-money', 'payment', [120, 56], self::PAYMENT_LOGO)->label('Logo Moov Money'),
                                        StorefrontImage::make('payment_logo.wave', 'payment', [120, 56], self::PAYMENT_LOGO)->label('Logo Wave'),
                                        StorefrontImage::make('payment_logo.cash-on-delivery', 'payment', [120, 56], self::PAYMENT_LOGO.' ; sans logo, le nom du moyen de paiement est affiché')->label('Logo « Paiement à la livraison »'),
                                    ]),
                            ]),
                        Tab::make('Newsletter')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema([
                                Section::make('Newsletter')
                                    ->description('Inscription dans le pied de page et fenêtre d’invitation. Les abonnés sont dans Promotions › Newsletter.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('newsletter.footer')->label('Inscription dans le pied de page'),
                                        Toggle::make('newsletter.popup')
                                            ->label('Fenêtre d’invitation')
                                            ->helperText('S’ouvre une seule fois par visiteur, après le délai ci-dessous ; jamais pendant un achat, un paiement ou dans l’espace client.'),
                                        TextInput::make('newsletter.title')->label('Titre du pied de page')->placeholder('Abonnez-vous à notre')->maxLength(60),
                                        TextInput::make('newsletter.highlight')->label('Mot mis en avant')->placeholder('newsletter')->maxLength(30),
                                        TextInput::make('newsletter.subtitle')->label('Sous-titre du pied de page')->maxLength(120)->columnSpanFull(),
                                        TextInput::make('newsletter.popup_title')->label('Titre de la fenêtre')->maxLength(60),
                                        TextInput::make('newsletter.popup_delay')->label('Délai avant ouverture')->integer()->minValue(3)->maxValue(120)->suffix('secondes'),
                                        Textarea::make('newsletter.popup_text')->label('Texte de la fenêtre')->rows(2)->maxLength(160)->columnSpanFull(),
                                        StorefrontImage::make('newsletter.popup_image', 'branding', [1388, 878], 'haut de la fenêtre')->label('Image de la fenêtre')->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Audience')
                            ->icon(Heroicon::OutlinedChartBar)
                            ->schema([
                                Section::make('Bandeau des cookies')
                                    ->description('La fenêtre qui demande l’accord du visiteur, en bas de l’écran, et le lien « Gérer les cookies » du pied de page. Toujours affichée dès qu’un outil de mesure ci-dessous est renseigné : l’accord est alors obligatoire.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('cookies.always')
                                            ->label('Afficher le bandeau même sans outil de mesure')
                                            ->helperText('Désactivé : sans Google Analytics, Meta ni TikTok, aucun bandeau (les cookies de la boutique elle-même, panier et connexion, n’en demandent pas).')
                                            ->columnSpanFull(),
                                        TextInput::make('cookies.title')->label('Titre')->placeholder(Analytics::BANNER_TEXTS['title'])->maxLength(80),
                                        TextInput::make('cookies.accept')->label('Bouton pour accepter')->placeholder(Analytics::BANNER_TEXTS['accept'])->maxLength(30),
                                        Textarea::make('cookies.message')->label('Message')->placeholder(Analytics::BANNER_TEXTS['message'])->rows(3)->maxLength(400)
                                            ->helperText('Suivi du lien « Politique de confidentialité ».'),
                                        TextInput::make('cookies.decline')->label('Bouton pour refuser')->placeholder(Analytics::BANNER_TEXTS['decline'])->maxLength(30),
                                    ]),
                                Section::make('Mesure d’audience')
                                    ->description('Chargés seulement après l’accord du visiteur (bandeau cookies). Laisser vide pour ne pas utiliser un service.')
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
                            ]),
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
            'identity.home_title' => $state['identity']['home_title'] ?? null,
            'newsletter.footer' => ($state['newsletter']['footer'] ?? false) ? '1' : '0',
            'newsletter.popup' => ($state['newsletter']['popup'] ?? false) ? '1' : '0',
            ...collect(ConfigOverrides::NEWSLETTER_TEXTS)->mapWithKeys(fn (string $field) => ["newsletter.{$field}" => $state['newsletter'][$field] ?? null])->all(),
            // An empty list is kept as "no guarantee" (the banner is hidden), not as "back to the defaults".
            'home.guarantees' => json_encode(array_values($state['home']['guarantees'] ?? []), JSON_UNESCAPED_UNICODE),
            'home.sections' => json_encode(collect($state['home']['sections'] ?? [])
                ->map(fn (array $section) => ['key' => $section['key'], 'visible' => (bool) ($section['visible'] ?? true)])
                ->values()->all()),
            'announcements.trending' => self::list($state['announcements']['trending'] ?? []),
            'announcements.campaign' => self::list($state['announcements']['campaign'] ?? []),
            'search.popular' => self::list($state['search']['popular'] ?? []),
            'search.placeholders' => self::list($state['search']['placeholders'] ?? []),
            'product_card.shipping_delay' => $state['product_card']['shipping_delay'] ?? null,
            'product_card.limited_stock_threshold' => $state['product_card']['limited_stock_threshold'] ?? null,
            'product_card.viewers_enabled' => ($state['product_card']['viewers_enabled'] ?? false) ? '1' : '0',
            'product_card.viewers_minimum' => $state['product_card']['viewers_minimum'] ?? null,
            'orders.weigh_tolerance' => $state['orders']['weigh_tolerance'] ?? null,
            'notifications.whatsapp' => ($state['notifications']['whatsapp'] ?? true) ? '1' : '0',
            'notifications.sms' => ($state['notifications']['sms'] ?? false) ? '1' : '0',
            ...collect(config('whatsapp.templates'))->mapWithKeys(fn (array $template, string $key) => ["whatsapp.twilio_content.{$key}" => $state['whatsapp_content'][$key] ?? null])->all(),
            'cookies.always' => ($state['cookies']['always'] ?? true) ? '1' : '0',
            ...collect(array_keys(Analytics::BANNER_TEXTS))->mapWithKeys(fn (string $key) => ["cookies.{$key}" => $state['cookies'][$key] ?? null])->all(),
            'market.enabled' => ($state['market']['enabled'] ?? true) ? '1' : '0',
            'market.in_menu' => ($state['market']['in_menu'] ?? true) ? '1' : '0',
            'market.category_id' => $state['market']['category_id'] ?? null,
            'market.per_row' => $state['market']['per_row'] ?? null,
            'market.title' => $state['market']['title'] ?? null,
            'market.subtitle' => $state['market']['subtitle'] ?? null,
            'market.banner' => $state['market']['banner'] ?? null,
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
