<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Models\Setting;
use App\Services\Storefront\StoreSettings;
use App\Services\Storefront\WhatsAppWidget;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Contenus › Bouton WhatsApp: the floating button of the storefront (App\Services\Storefront\WhatsAppWidget) set up
 * without a developer, with a live preview of the chat window it opens. The number is the store's WhatsApp number,
 * the same as in Paramètres › Coordonnées.
 */
class WhatsAppButton extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleOvalLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Bouton WhatsApp';

    protected static ?string $navigationLabel = 'Bouton WhatsApp';

    protected static ?string $slug = 'bouton-whatsapp';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageContent->value);
    }

    public function getSubheading(): string
    {
        return 'Le bouton flottant qui permet à vos visiteurs de vous écrire en un clic.';
    }

    public function mount(): void
    {
        $this->form->fill([
            ...app(WhatsAppWidget::class)->settings(),
            'phone' => app(StoreSettings::class)->contact()['whatsapp'] ?? '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 5])->schema([
                    Group::make([
                        Section::make()->schema([
                            Toggle::make('enabled')
                                ->label('Bouton actif sur le site')
                                ->helperText('Affiché en bas de toutes les pages du site.')
                                ->onColor('success')
                                ->live(),
                            TextInput::make('phone')
                                ->label('Numéro WhatsApp')
                                ->helperText('Format international avec l’indicatif du pays, sans « + » ni espace : 2250700000000. Le même numéro que dans Paramètres › Coordonnées.')
                                ->tel()
                                ->required()
                                ->regex('/^\d{8,15}$/')
                                ->maxLength(15)
                                ->live(onBlur: true),
                        ]),
                        Section::make('Message pré-rempli')
                            ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                            ->schema([
                                Textarea::make('message')
                                    ->hiddenLabel()
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText('Texte déjà écrit dans WhatsApp quand le visiteur ouvre la conversation ; il peut le modifier avant d’envoyer. Sur une fiche produit ou une confirmation de commande, le message parle de l’article ou de la commande.')
                                    ->live(onBlur: true),
                                Toggle::make('include_page')
                                    ->label('Ajouter le lien de la page consultée')
                                    ->helperText('Très utile sur une fiche produit : vous savez immédiatement de quel article parle le client.')
                                    ->onColor('success'),
                            ]),
                        Section::make('Fenêtre de discussion')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->description('Au clic, une fenêtre façon WhatsApp s’ouvre avec un message d’accueil. Désactivée, le bouton ouvre directement WhatsApp.')
                            ->columns(2)
                            ->schema([
                                Toggle::make('greeting_enabled')->label('Fenêtre de discussion')->onColor('success')->live()->columnSpanFull(),
                                TextInput::make('agent_name')->label('Nom affiché')->maxLength(60)->live(onBlur: true)
                                    ->visible(fn (Get $get) => $get('greeting_enabled')),
                                TextInput::make('status_text')->label('Statut')->maxLength(80)->live(onBlur: true)
                                    ->visible(fn (Get $get) => $get('greeting_enabled')),
                                Textarea::make('greeting')->label('Message d’accueil')->rows(2)->maxLength(200)->live(onBlur: true)->columnSpanFull()
                                    ->visible(fn (Get $get) => $get('greeting_enabled')),
                                TextInput::make('button_label')->label('Texte du bouton')->maxLength(40)->live(onBlur: true)
                                    ->visible(fn (Get $get) => $get('greeting_enabled')),
                                TextInput::make('greeting_delay')
                                    ->label('Ouverture automatique (secondes)')
                                    ->helperText('0 : jamais. Une seule fois par visite, sur ordinateur ; sur mobile, une simple pastille apparaît pour ne pas gêner.')
                                    ->numeric()->minValue(0)->maxValue(60)->suffix('s')
                                    ->visible(fn (Get $get) => $get('greeting_enabled')),
                            ]),
                        Section::make('Affichage')
                            ->icon(Heroicon::OutlinedSquares2x2)
                            ->schema([
                                ToggleButtons::make('position')
                                    ->label('Position')
                                    ->options(['left' => 'En bas à gauche', 'right' => 'En bas à droite'])
                                    ->icons(['left' => Heroicon::OutlinedArrowDownLeft, 'right' => Heroicon::OutlinedArrowDownRight])
                                    ->inline()
                                    ->live(),
                                Toggle::make('show_desktop')->label('Afficher sur ordinateur')->onColor('success'),
                                Toggle::make('show_mobile')->label('Afficher sur mobile')->onColor('success'),
                                Toggle::make('hide_on_checkout')
                                    ->label('Masquer pendant la commande et le paiement')
                                    ->helperText('Évite de détourner l’attention du client au moment de payer. Le bouton reste visible sur la confirmation de commande.')
                                    ->onColor('success'),
                            ]),
                    ])->columnSpan(['lg' => 3]),
                    View::make('filament.pages.whatsapp-button-preview')
                        ->viewData(fn () => ['preview' => $this->data, 'storeUrl' => url('/')])
                        ->columnSpan(['lg' => 2]),
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
        $flag = fn (string $key) => ($state[$key] ?? false) ? '1' : '0';

        Setting::store([
            'contact.whatsapp' => $state['phone'] ?? null,
            'whatsapp_widget.enabled' => $flag('enabled'),
            'whatsapp_widget.message' => $state['message'] ?? null,
            'whatsapp_widget.include_page' => $flag('include_page'),
            'whatsapp_widget.greeting_enabled' => $flag('greeting_enabled'),
            // Fields hidden with the window keep their saved value.
            ...collect(['agent_name', 'status_text', 'greeting', 'button_label', 'greeting_delay'])
                ->filter(fn (string $key) => array_key_exists($key, $state))
                ->mapWithKeys(fn (string $key) => ["whatsapp_widget.{$key}" => $state[$key]])->all(),
            'whatsapp_widget.position' => $state['position'] ?? 'left',
            'whatsapp_widget.show_desktop' => $flag('show_desktop'),
            'whatsapp_widget.show_mobile' => $flag('show_mobile'),
            'whatsapp_widget.hide_on_checkout' => $flag('hide_on_checkout'),
        ]);

        Notification::make()->title('Bouton WhatsApp enregistré')->success()->send();
    }
}
