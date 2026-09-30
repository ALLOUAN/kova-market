<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Enums\Permission;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\BadgeVariant;
use App\Filament\Support\SlugInput;
use App\Filament\Support\StorefrontImage;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Sub-categories of a category, managed from its page: added, edited, put in order (drag and drop) and removed
 * without leaving it. The tree has three levels, so a third-level category has none.
 */
class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Sous-catégories';

    protected static ?string $modelLabel = 'sous-catégorie';

    protected static ?string $pluralModelLabel = 'sous-catégories';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedSquares2x2;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var Category $ownerRecord */
        return $ownerRecord->parent?->parent_id === null;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Category $ownerRecord */
        $count = $ownerRecord->children()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->can(Permission::ManageCatalog->value);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                ...SlugInput::make(),
                TextInput::make('tagline')
                    ->label('Accroche')
                    ->maxLength(255),
                TextInput::make('icon')
                    ->label('Icône (Font Awesome)')
                    ->placeholder('fa-regular fa-camera')
                    ->maxLength(255),
                TextInput::make('position')
                    ->label('Ordre d’affichage')
                    ->helperText('Vous pouvez aussi glisser les lignes du tableau.')
                    ->numeric()
                    ->minValue(0)
                    ->default(fn () => (int) $this->getOwnerRecord()->children()->max('position') + 1)
                    ->required(),
                Toggle::make('is_featured')
                    ->label('Mise en avant sur l’accueil')
                    ->inline(false),
                StorefrontImage::make('image', 'categories', [176, 176], 'carrée, fond transparent')
                    ->label('Image')
                    ->columnSpanFull(),
                TextInput::make('badge_label')
                    ->label('Badge')
                    ->placeholder('NOUVEAU')
                    ->maxLength(30),
                Select::make('badge_variant')
                    ->label('Couleur du badge')
                    ->options(BadgeVariant::options())
                    ->requiredWith('badge_label'),
            ]);
    }

    public function table(Table $table): Table
    {
        $isSecondLevel = $this->getOwnerRecord()->parent_id !== null;

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['products', 'children']))
            ->recordTitleAttribute('name')
            ->description($isSecondLevel
                ? 'Sous-catégories de troisième niveau (le dernier) : elles s’affichent dans le panneau des catégories et sur la page de « '.$this->getOwnerRecord()->name.' ».'
                : 'Elles s’affichent dans le méga-menu « Boutique », le menu mobile, le panneau des catégories et sur la page de « '.$this->getOwnerRecord()->name.' ». Glissez les lignes pour changer leur ordre.')
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('storefront')
                    ->square(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->description(fn (Category $record) => $record->tagline)
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('Produits'),
                TextColumn::make('children_count')
                    ->label('Sous-catégories')
                    ->hidden($isSecondLevel),
                IconColumn::make('is_featured')
                    ->label('À l’accueil')
                    ->boolean(),
                TextColumn::make('position')
                    ->label('Ordre'),
            ])
            ->emptyStateHeading('Aucune sous-catégorie')
            ->emptyStateDescription('Ajoutez-en une pour ranger les produits de « '.$this->getOwnerRecord()->name.' » plus finement.')
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une sous-catégorie')
                    ->modalHeading('Ajouter une sous-catégorie à « '.$this->getOwnerRecord()->name.' »')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->recordActions([
                EditAction::make()->label('Modifier'),
                Action::make('open')
                    ->label('Fiche complète')
                    ->tooltip('Référencement, encart du menu et ses propres sous-catégories')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Category $record) => CategoryResource::getUrl('edit', ['record' => $record])),
                // Products block the deletion (database constraint) and sub-categories would be deleted with it.
                DeleteAction::make()
                    ->hidden(fn (Category $record) => $record->products_count > 0 || $record->children_count > 0),
            ]);
    }
}
