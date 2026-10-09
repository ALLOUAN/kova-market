<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Brands\Widgets\BrandsOverview;
use App\Models\Brand;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandResource::class;

    /**
     * The header band (BrandsOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [BrandsOverview::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'with_products' => Tab::make('Avec produits')->modifyQueryUsing(fn (Builder $query) => $query->has('products')),
            'empty' => Tab::make('Sans produit')->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('products'))
                ->badge(fn () => Brand::query()->doesntHave('products')->count() ?: null)->badgeColor('warning'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une marque')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
