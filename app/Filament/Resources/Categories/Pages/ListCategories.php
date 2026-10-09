<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Widgets\CategoriesOverview;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    /**
     * The header band (CategoriesOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [CategoriesOverview::make(['scope' => 'all'])];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une catégorie')->icon(Heroicon::OutlinedPlus),
        ];
    }

    public function getTabs(): array
    {
        $empty = fn (Builder $query) => $query->doesntHave('products')->doesntHave('children');

        return [
            'all' => Tab::make('Toutes')
                ->badge(Category::count()),
            'roots' => Tab::make('Rayons principaux')
                ->badge(Category::whereNull('parent_id')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parent_id')),
            'children' => Tab::make('Sous-catégories')
                ->badge(Category::whereNotNull('parent_id')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('parent_id')),
            'featured' => Tab::make('À l’accueil')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_featured', true)),
            'empty' => Tab::make('Sans produit')
                ->badge(fn () => $empty(Category::query())->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing($empty),
        ];
    }
}
