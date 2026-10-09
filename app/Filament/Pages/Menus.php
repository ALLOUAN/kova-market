<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\Settings\Widgets\MenusOverview;
use App\Models\Setting;
use App\Services\Storefront\ConfigOverrides;
use App\Support\MenuLinks;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Menus of the storefront edited without a developer (F-111): the "Pages" and "Aide" menus of the header, the footer
 * columns, the legal links and the links of the category side panel. The "Boutique" menu stays built from the
 * category tree. Saved as settings laid over config/navigation.php (ConfigOverrides); "Rétablir" goes back to it.
 */
class Menus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Menus du site';

    protected static ?string $navigationLabel = 'Menus';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /**
     * The header band (MenusOverview) carries the title and the size of each menu.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [MenusOverview::class];
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageContent->value);
    }

    public function mount(): void
    {
        $this->form->fill(self::current());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Menu « Pages » de l’en-tête')
                    ->description('Le grand menu déroulant, en colonnes. Le menu « Boutique » reste construit à partir des catégories.')
                    ->schema([self::groups('pages', 'Ajouter une colonne')]),
                Section::make('Menu « Aide » de l’en-tête')
                    ->schema([self::links('help')]),
                Section::make('Colonnes du pied de page')
                    ->schema([self::groups('footer', 'Ajouter une colonne')]),
                Section::make('Liens légaux (tout en bas de page)')
                    ->schema([self::links('legal')]),
                Section::make('Panneau latéral des catégories')
                    ->description('Les liens sous la liste des catégories, dans le panneau qui s’ouvre depuis l’en-tête.')
                    ->schema([self::groups('sidebar', 'Ajouter un groupe')]),
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Rétablir les menus d’origine')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Les menus reviennent à ceux livrés avec le site ; vos modifications sont perdues.')
                ->action(function (): void {
                    Setting::whereIn('key', ConfigOverrides::MENUS)->delete();
                    Setting::forgetCache();
                    activity()->causedBy(auth()->user())->log('Menus du site rétablis');
                    Notification::make()->title('Menus rétablis')->success()->send();
                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $groups = fn (array $groups) => collect($groups)->map(fn (array $group) => [
            'title' => trim((string) $group['title']),
            'links' => collect($group['links'] ?? [])->map(fn (array $link) => MenuLinks::toConfig($link))->values()->all(),
        ])->values()->all();
        $links = fn (array $items) => collect($items)->map(fn (array $link) => MenuLinks::toConfig($link))->values()->all();

        Setting::store([
            'menu.pages' => json_encode($groups($state['pages'] ?? []), JSON_UNESCAPED_UNICODE),
            'menu.help' => json_encode($links($state['help'] ?? []), JSON_UNESCAPED_UNICODE),
            'menu.footer' => json_encode($groups($state['footer'] ?? []), JSON_UNESCAPED_UNICODE),
            'menu.legal' => json_encode($links($state['legal'] ?? []), JSON_UNESCAPED_UNICODE),
            'menu.sidebar' => json_encode($groups($state['sidebar'] ?? []), JSON_UNESCAPED_UNICODE),
        ]);

        activity()->causedBy(auth()->user())->log('Menus du site modifiés');

        Notification::make()->title('Menus enregistrés')->success()->send();
    }

    /**
     * The menus in use, as form state.
     *
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        $main = collect(config('navigation.main'));
        $links = fn (array $items) => collect($items)->map(fn (array $link) => MenuLinks::toForm($link))->values()->all();
        $groups = fn (array $groups) => collect($groups)->map(fn (array $groupLinks, string $title) => ['title' => $title, 'links' => $links($groupLinks)])->values()->all();

        return [
            'pages' => collect($main->firstWhere('type', 'mega')['columns'] ?? [])
                ->map(fn (array $column) => ['title' => $column['title'], 'links' => $links($column['links'] ?? [])])
                ->values()->all(),
            'help' => $links($main->firstWhere('type', 'dropdown')['links'] ?? []),
            'footer' => $groups(config('navigation.footer')),
            'legal' => $links(config('navigation.legal')),
            'sidebar' => $groups(config('navigation.sidebar')),
        ];
    }

    /**
     * Named groups of links (columns of a menu).
     */
    private static function groups(string $name, string $addLabel): Repeater
    {
        return Repeater::make($name)
            ->hiddenLabel()
            ->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(60),
                self::links('links'),
            ])
            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
            ->addActionLabel($addLabel)
            ->reorderableWithButtons()
            ->collapsible()
            ->defaultItems(0);
    }

    /**
     * A list of links: label, destination (a page of the site or an address), optional badge.
     */
    private static function links(string $name): Repeater
    {
        return Repeater::make($name)
            ->label($name === 'links' ? 'Liens' : null)
            ->hiddenLabel($name !== 'links')
            ->schema([
                TextInput::make('label')->label('Libellé')->required()->maxLength(60),
                Select::make('target')
                    ->label('Destination')
                    ->options(fn () => MenuLinks::destinations())
                    ->searchable()
                    ->required()
                    ->live(),
                TextInput::make('url')
                    ->label('Adresse')
                    ->placeholder('https://… ou /…')
                    ->visible(fn (Get $get) => $get('target') === MenuLinks::CUSTOM)
                    ->required(fn (Get $get) => $get('target') === MenuLinks::CUSTOM)
                    ->regex('#^(https?://|/)#')
                    ->validationMessages(['regex' => 'L’adresse doit commencer par https:// ou /.'])
                    ->maxLength(255),
                TextInput::make('badge_label')->label('Pastille (facultatif)')->placeholder('Nouveau')->maxLength(12),
                Select::make('badge_variant')
                    ->label('Couleur de la pastille')
                    ->options(['green' => 'Vert', 'yellow' => 'Jaune', 'danger' => 'Rouge'])
                    ->visible(fn (Get $get) => filled($get('badge_label'))),
            ])
            ->columns(2)
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
            ->addActionLabel('Ajouter un lien')
            ->reorderableWithButtons()
            ->collapsible()
            ->collapsed()
            ->defaultItems(0);
    }
}
