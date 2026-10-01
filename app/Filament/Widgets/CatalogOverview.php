<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\Promotion;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Catalog figures of the dashboard (F-109). Sales figures join them with the orders module.
 */
class CatalogOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Catalogue';

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewCatalog->value);
    }

    protected function getStats(): array
    {
        $threshold = config('storefront.product_card.limited_stock_threshold');

        $online = Product::active()->count();
        $soldOut = Product::active()->where('stock', 0)->count();
        $lowStock = Product::active()->lowStock($threshold)->count();
        $campaigns = Promotion::query()->where('starts_at', '<=', now())->where('ends_at', '>=', now())->count();

        return [
            Stat::make('Produits en ligne', $online)
                ->icon(Heroicon::OutlinedCube)
                ->description(Product::query()->where('is_active', false)->count().' hors ligne'),
            Stat::make('En rupture de stock', $soldOut)
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color($soldOut > 0 ? 'danger' : 'success')
                ->description('Produits en ligne à 0'),
            Stat::make('Stock bas', $lowStock)
                ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                ->color($lowStock > 0 ? 'warning' : 'success')
                ->description("{$threshold} unités ou moins"),
            Stat::make('Offres spéciales en cours', $campaigns)
                ->icon(Heroicon::OutlinedTag),
        ];
    }
}
