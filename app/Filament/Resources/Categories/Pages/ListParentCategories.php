<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The main departments of the store (sidebar: Catalogue › Catégories parentes), in their menu order: dragging a row
 * changes the order of the "Boutique" menu, the mobile menu and the category panel.
 */
class ListParentCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $title = 'Catégories parentes';

    protected static ?string $breadcrumb = 'Catégories parentes';

    public function getSubheading(): ?string
    {
        return 'Les rayons principaux de la boutique, dans l’ordre des menus. Glissez les lignes pour changer cet ordre ; ouvrez un rayon pour gérer ses sous-catégories.';
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);
        // All of them are main departments: nothing to attach to, nothing to filter by level.
        $table->getColumn('parent.name')?->hidden();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parent_id')->withCount(['products', 'children']))
            ->filters([])
            ->reorderable('position')
            ->defaultSort('position')
            ->emptyStateHeading('Aucune catégorie parente')
            ->emptyStateDescription('Commencez par les rayons principaux de la boutique, par exemple « Téléphones » ou « Audio ».');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ajouter une catégorie parente')
                ->icon(Heroicon::OutlinedPlus)
                ->url(CategoryResource::getUrl('create', ['type' => CreateCategory::PARENT_CATEGORY])),
        ];
    }
}
