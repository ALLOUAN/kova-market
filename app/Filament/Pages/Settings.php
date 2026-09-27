<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Models\Setting;
use App\Services\Storefront\StoreSettings;
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
        ]);

        activity()->causedBy(auth()->user())->withProperties(['keys' => array_keys($state)])->log('Paramètres de la boutique modifiés');

        Notification::make()->title('Paramètres enregistrés')->success()->send();
    }
}
