<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Widgets\CategoriesOverview;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every sub-category in one place, grouped by parent category (sidebar: Catalogue › Sous-catégories).
 */
class ListSubCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $title = 'Sous-catégories';

    protected static ?string $breadcrumb = 'Sous-catégories';

    /**
     * The header band (CategoriesOverview) carries the title, the explanation and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [CategoriesOverview::make(['scope' => 'sub'])];
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);
        // The group title already names the parent.
        $table->getColumn('parent.name')?->hidden();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereNotNull('parent_id')
                ->with('parent.parent')
                ->withCount(['products', 'children']))
            ->groups([
                Group::make('parent.name')
                    ->label('Catégorie parente')
                    ->titlePrefixedWithLabel(false)
                    // Parents in alphabetical order, each one's sub-categories together in their display order.
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query
                        ->orderBy(Category::query()->from('categories as parents')->select('parents.name')->whereColumn('parents.id', 'categories.parent_id'), $direction)
                        ->orderBy('categories.parent_id'))
                    ->getDescriptionFromRecordUsing(fn (Category $record) => $record->parent?->parent
                        ? 'Dans « '.$record->parent->parent->name.' »'
                        : 'Rayon principal')
                    ->collapsible(),
            ])
            ->defaultGroup('parent.name')
            ->defaultSort('position')
            ->emptyStateHeading('Aucune sous-catégorie')
            ->emptyStateDescription('Ajoutez-en une pour ranger les produits d’un rayon plus finement.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ajouter une sous-catégorie')
                ->icon(Heroicon::OutlinedPlus)
                ->url(CategoryResource::getUrl('create', ['type' => CreateCategory::SUB_CATEGORY])),
        ];
    }
}
