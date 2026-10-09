<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Widgets\ProductsOverview;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /**
     * The header band (ProductsOverview) carries the title and the figures.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [ProductsOverview::class];
    }

    /**
     * One click to the products needing attention; the figures of the header band open these tabs.
     */
    public function getTabs(): array
    {
        $lowStock = fn (Builder $query) => $query->lowStock(config('storefront.product_card.limited_stock_threshold'));
        $noDescription = fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('description')->orWhere('description', ''));

        return [
            'all' => Tab::make('Tous'),
            'online' => Tab::make('En ligne')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'offline' => Tab::make('Hors ligne')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            'sold_out' => Tab::make('En rupture')->modifyQueryUsing(fn (Builder $query) => $query->where('stock', 0))
                ->badge(fn () => Product::query()->where('stock', 0)->count() ?: null)->badgeColor('danger'),
            'low_stock' => Tab::make('Stock bas')->modifyQueryUsing($lowStock)
                ->badge(fn () => $lowStock(Product::query())->count() ?: null)->badgeColor('warning'),
            'on_sale' => Tab::make('En promotion')->modifyQueryUsing(fn (Builder $query) => $query->whereColumn('compare_at_price', '>', 'price')),
            // Product pages with nothing to read: worth writing, best sellers first.
            'no_description' => Tab::make('Sans description')->modifyQueryUsing($noDescription)
                ->badge(fn () => $noDescription(Product::query()->where('is_active', true))->count() ?: null)->badgeColor('gray'),
            'best_sellers' => Tab::make('Meilleures ventes')->modifyQueryUsing(fn (Builder $query) => $query->where('sold_count', '>', 0)->reorder('sold_count', 'desc')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter un produit')->icon('heroicon-o-plus'),
        ];
    }
}
