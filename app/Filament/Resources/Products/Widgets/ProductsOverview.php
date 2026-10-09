<?php

namespace App\Filament\Resources\Products\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Product;

/**
 * Header of the products list: the catalogue in one sentence, then online, sold out, low stock, on sale and best
 * seller, each card opening the matching tab.
 */
class ProductsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $total = Product::query()->count();
        $online = Product::query()->where('is_active', true)->count();
        $soldOut = Product::query()->where('stock', 0)->count();
        $low = Product::query()->lowStock(config('storefront.product_card.limited_stock_threshold'))->count();
        $onSale = Product::query()->whereColumn('compare_at_price', '>', 'price')->count();
        $waiting = Product::query()->whereHas('stockAlerts', fn ($alerts) => $alerts->whereNull('notified_at'))->count();
        $best = Product::query()->where('sold_count', '>', 0)->orderByDesc('sold_count')->first(['name', 'sold_count']);
        $tab = fn (string $tab) => ProductResource::getUrl('index', ['tab' => $tab]);

        $alerts = array_filter([
            $soldOut > 0 ? '<strong>'.$soldOut.'</strong> en rupture' : null,
            $low > 0 ? '<strong>'.$low.'</strong> à réapprovisionner' : null,
        ]);

        return [
            'title' => 'Produits',
            'icon' => 'heroicon-o-cube',
            'lead' => '<strong>'.$online.'</strong> produit'.($online > 1 ? 's' : '').' en ligne sur '.$total
                .($alerts !== [] ? ', '.implode(' et ', $alerts).'.' : '. Tout le catalogue est en stock.'),
            'kpis' => [
                self::kpi('En ligne', (string) $online, ($total - $online).' hors ligne · '.$total.' au catalogue',
                    'heroicon-o-eye', 'navy', $tab('online')),
                self::kpi('En rupture', (string) $soldOut, $waiting > 0 ? "<strong>{$waiting}</strong> produit(s) attendu(s) par des clients" : 'Aucun client en attente',
                    'heroicon-o-x-circle', $soldOut > 0 ? 'red' : 'green', $tab('sold_out'), $soldOut > 0 ? 'red' : null),
                self::kpi('Stock bas', (string) $low, 'À réapprovisionner bientôt',
                    'heroicon-o-exclamation-triangle', 'gold', $tab('low_stock'), $low > 0 ? 'gold' : null),
                self::kpi('En promotion', (string) $onSale, 'Avec un prix barré en boutique',
                    'heroicon-o-tag', 'orange', $tab('on_sale')),
                self::kpi('Meilleure vente', $best ? (string) $best->sold_count : '—', $best ? e($best->name) : 'Aucune vente pour le moment',
                    'heroicon-o-trophy', 'green', $tab('best_sellers')),
            ],
        ];
    }
}
