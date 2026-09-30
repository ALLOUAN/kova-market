<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Models\Setting;
use App\Services\Storefront\Maintenance as MaintenanceMode;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Maintenance mode (App\Services\Storefront\Maintenance): puts the storefront behind the maintenance page for
 * visitors, while the team, the back-office, the courier app and CinetPay keep working.
 */
class Maintenance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Maintenance';

    protected static ?string $title = 'Mode maintenance';

    protected static ?string $slug = 'maintenance';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageSettings->value);
    }

    public static function getNavigationBadge(): ?string
    {
        return app(MaintenanceMode::class)->enabled() ? 'Active' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function getSubheading(): ?string
    {
        return 'Contrôlez l’accès au site pour les visiteurs. Votre équipe connectée au back-office continue de voir le site normalement.';
    }

    public function mount(): void
    {
        $maintenance = self::maintenance();

        $this->form->fill([
            'message' => $maintenance->message(),
            'duration' => $maintenance->duration(),
            'progress' => $maintenance->progress(),
            'allowed_ips' => implode("\n", $maintenance->allowedIps()),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Paramètres de la maintenance')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->description('Ce que voient les visiteurs sur la page de maintenance. Modifiable à tout moment, même pendant la maintenance.')
                    ->columns(2)
                    ->schema([
                        Textarea::make('message')
                            ->label('Message affiché aux visiteurs')
                            ->placeholder(MaintenanceMode::DEFAULT_MESSAGE)
                            ->helperText('Affiché sous le titre « Nous revenons très vite ».')
                            ->rows(3)
                            ->maxLength(500)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('duration')
                            ->label('Durée estimée (en heures)')
                            ->numeric()
                            ->minValue(0.5)
                            ->maxValue(72)
                            ->step(0.5)
                            ->suffix('h')
                            ->required()
                            ->helperText('De 0,5 à 72 heures. Avec l’heure d’activation, la page affiche l’heure de retour prévue.'),
                        Slider::make('progress')
                            ->label('Progression des travaux (%)')
                            ->range(0, 100)
                            ->step(1)
                            ->tooltips()
                            ->fillTrack()
                            ->helperText('Barre de progression affichée aux visiteurs.'),
                        Textarea::make('allowed_ips')
                            ->label('Adresses IP autorisées')
                            ->placeholder("Une adresse IP par ligne\n192.168.1.1\n10.0.0.0/24")
                            ->rows(3)
                            ->extraInputAttributes(['class' => 'font-mono'])
                            ->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                $invalid = collect(MaintenanceMode::parseIps((string) $value))->reject(fn (string $ip) => MaintenanceMode::isValidIp($ip));
                                if ($invalid->isNotEmpty()) {
                                    $fail('Adresse IP non valable : '.$invalid->implode(', ').'.');
                                }
                            }])
                            ->helperText(new HtmlString('Ces adresses voient le site même pendant la maintenance (une par ligne, plages CIDR acceptées). Votre adresse IP actuelle : <code>'.e(request()->ip()).'</code>'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        $maintenance = self::maintenance();
        $on = $maintenance->enabled();

        return $schema->components([
            Callout::make($on ? 'Le site est en maintenance' : 'Le site est en ligne')
                ->icon($on ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedGlobeAlt)
                ->color($on ? 'danger' : 'success')
                ->description($on
                    ? 'Les visiteurs voient la page de maintenance'.($maintenance->startedAt() ? ' depuis le '.$maintenance->startedAt()->format('d/m/Y à H\hi') : '').'. Le back-office, l’application livreur et les paiements en ligne continuent de fonctionner.'
                    : 'Tous les visiteurs ont accès au site normalement.')
                ->actions([$this->toggleAction(), $this->previewAction()]),

            Section::make('Résumé')
                ->icon(Heroicon::OutlinedChartBar)
                ->schema([
                    Grid::make(['default' => 2, 'lg' => 5])->schema([
                        TextEntry::make('summary_status')->label('Statut')
                            ->state($on ? 'En maintenance' : 'En ligne')->badge()->color($on ? 'danger' : 'success'),
                        TextEntry::make('summary_progress')->label('Progression')->state($maintenance->progress().' %'),
                        TextEntry::make('summary_duration')->label('Durée estimée')->state($maintenance->durationLabel()),
                        TextEntry::make('summary_back')->label('Retour prévu')
                            ->state($on && $maintenance->expectedBackAt() ? $maintenance->expectedBackAt()->format('d/m à H\hi') : '—'),
                        TextEntry::make('summary_ips')->label('IP autorisées')->state((string) count($maintenance->allowedIps())),
                    ]),
                ]),

            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Enregistrer les paramètres')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::store([
            'maintenance.message' => trim((string) $state['message']),
            'maintenance.duration' => (string) (float) $state['duration'],
            'maintenance.progress' => (string) (int) ($state['progress'] ?? 0),
            'maintenance.allowed_ips' => implode("\n", MaintenanceMode::parseIps((string) ($state['allowed_ips'] ?? ''))),
        ]);

        activity()->causedBy(auth()->user())->log('Paramètres de la maintenance modifiés');

        Notification::make()->title('Paramètres de la maintenance enregistrés')->success()->send();
        $this->redirect(static::getUrl());
    }

    public function toggleAction(): Action
    {
        $on = self::maintenance()->enabled();

        return Action::make('toggle')
            ->label($on ? 'Remettre le site en ligne' : 'Activer la maintenance')
            ->icon($on ? Heroicon::OutlinedPlayCircle : Heroicon::OutlinedPauseCircle)
            ->color($on ? 'success' : 'danger')
            ->button()
            ->requiresConfirmation()
            ->modalHeading($on ? 'Remettre le site en ligne ?' : 'Activer la maintenance ?')
            ->modalDescription($on
                ? 'Tous les visiteurs retrouvent la boutique immédiatement.'
                : 'Les visiteurs verront la page de maintenance et ne pourront plus commander. Votre équipe connectée et les adresses IP autorisées gardent l’accès au site.')
            ->modalSubmitActionLabel($on ? 'Remettre en ligne' : 'Activer la maintenance')
            ->action(function (): void {
                $maintenance = self::maintenance();

                if ($maintenance->enabled()) {
                    $maintenance->disable();
                    activity()->causedBy(auth()->user())->log('Maintenance désactivée : site remis en ligne');
                    Notification::make()->title('Le site est de nouveau en ligne')->success()->send();
                } else {
                    $maintenance->enable();
                    activity()->causedBy(auth()->user())->log('Maintenance activée');
                    Notification::make()->title('Maintenance activée')->body('Les visiteurs voient maintenant la page de maintenance.')->warning()->send();
                }

                $this->redirect(static::getUrl());
            });
    }

    public function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Voir la page de maintenance')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->button()
            ->outlined()
            ->url(route('maintenance.preview'), shouldOpenInNewTab: true);
    }

    private static function maintenance(): MaintenanceMode
    {
        return app(MaintenanceMode::class);
    }
}
