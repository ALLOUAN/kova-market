<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Services\Catalog\Import\CatalogImporter;
use App\Services\Catalog\Import\ImportException;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Catalog import from a CSV file (F-104): the file is first analysed (preview, nothing saved), then imported;
 * both show the lines refused and why.
 */
class ImportCatalog extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Importer (CSV)';

    protected static ?string $title = 'Importer le catalogue';

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** File analysed and waiting for the import, on the local disk. */
    public ?string $analysedFile = null;

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageCatalog->value);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Fichier')
                    ->description('Une ligne par variante (SKU). Un SKU connu met à jour sa variante et son produit ; un nouveau SKU crée le produit, ou une variante du produit de même slug. Laisser une cellule vide la vide aussi (promotion, seuil), sauf le stock : vide, il n’est pas modifié.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Fichier CSV')
                            ->helperText('CSV séparé par des points-virgules ou des virgules, en UTF-8 (« CSV UTF-8 » dans Excel), 5 Mo maximum.')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                            ->maxSize(5120)
                            ->required(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('analyse')
                ->footer([
                    Actions::make([
                        Action::make('analyse')->label('Analyser le fichier')->submit('analyse'),
                        Action::make('template')
                            ->label('Télécharger le modèle')
                            ->color('gray')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->action(fn (): StreamedResponse => response()->streamDownload(
                                fn () => print (CatalogImporter::template()),
                                'modele-import-catalogue.csv',
                                ['Content-Type' => 'text/csv; charset=UTF-8'],
                            )),
                    ]),
                ]),
            View::make('filament.pages.import-catalog-report')
                ->viewData(fn () => ['report' => $this->report])
                ->visible(fn () => $this->report !== null),
        ]);
    }

    /**
     * Offered once a file has been analysed and has lines to import.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label(fn () => 'Importer '.($this->report['imported'] ?? 0).' ligne(s)')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalDescription('Les lignes refusées seront ignorées. Les autres sont enregistrées tout de suite.')
                ->visible(fn () => $this->analysedFile !== null && ($this->report['imported'] ?? 0) > 0)
                ->action(fn () => $this->import()),
        ];
    }

    public function analyse(CatalogImporter $importer): void
    {
        $this->forgetAnalysedFile();
        $file = $this->form->getState()['file'];

        try {
            $this->report = $importer->preview(Storage::disk('local')->path($file))->toArray();
            $this->analysedFile = $file;
        } catch (ImportException $exception) {
            Storage::disk('local')->delete($file);
            $this->report = null;
            Notification::make()->title('Fichier refusé')->body($exception->getMessage())->danger()->send();
        }
    }

    public function import(): void
    {
        if ($this->analysedFile === null) {
            return;
        }

        try {
            $report = app(CatalogImporter::class)->import(Storage::disk('local')->path($this->analysedFile), auth()->user());
        } catch (ImportException $exception) {
            Notification::make()->title('Import impossible')->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->report = $report->toArray();
        $this->forgetAnalysedFile();
        $this->form->fill();

        Notification::make()
            ->title('Import terminé')
            ->body("{$report->imported()} ligne(s) importée(s), ".count($report->errors).' refusée(s).')
            ->success()
            ->send();
    }

    private function forgetAnalysedFile(): void
    {
        if ($this->analysedFile !== null) {
            Storage::disk('local')->delete($this->analysedFile);
            $this->analysedFile = null;
        }
    }
}
