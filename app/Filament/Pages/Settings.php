<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Models\Setting;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Storefront\StoreSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
            'payment' => ['cash_on_delivery_limit' => Setting::get('payment.cash_on_delivery_limit')],
            'analytics' => collect(self::ANALYTICS_FIELDS)->mapWithKeys(fn (string $field) => [$field => Setting::get("analytics.{$field}")])->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
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
            ...collect(self::ANALYTICS_FIELDS)->mapWithKeys(fn (string $field) => ["analytics.{$field}" => $state['analytics'][$field] ?? null])->all(),
        ]);

        activity()->causedBy(auth()->user())->withProperties(['keys' => array_keys($state)])->log('Paramètres de la boutique modifiés');

        Notification::make()->title('Paramètres enregistrés')->success()->send();
    }
}
