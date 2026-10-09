<?php

namespace App\Filament\Resources\Brands\Widgets;

use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Brand;
use App\Models\Product;

/**
 * Header of the brands list: how many, with or without products, the products without a brand and the
 * brand with the most products.
 */
class BrandsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $total = Brand::query()->count();
        $withProducts = Brand::query()->has('products')->count();
        $unbranded = Product::query()->whereNull('brand_id')->count();
        $top = Brand::query()->withCount('products')->orderByDesc('products_count')->first();
        $tab = fn (string $tab) => BrandResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Marques',
            'icon' => 'heroicon-o-check-badge',
            'lead' => '<strong>'.$total.'</strong> marque'.($total > 1 ? 's' : '').', dont <strong>'.$withProducts.'</strong> avec des produits. Glissez les lignes pour changer leur ordre en boutique.',
            'kpis' => [
                self::kpi('Marques', (string) $total, $withProducts.' avec des produits',
                    'heroicon-o-check-badge', 'navy', $tab('all')),
                self::kpi('Sans produit', (string) ($total - $withProducts), 'Pas encore visibles en boutique',
                    'heroicon-o-inbox', 'gold', $tab('empty'), $total - $withProducts > 0 ? 'gold' : null),
                self::kpi('Produits sans marque', (string) $unbranded, 'À rattacher depuis leur fiche',
                    'heroicon-o-cube', 'orange'),
                self::kpi('La plus fournie', $top ? (string) $top->products_count : '—', $top ? e($top->name) : 'Aucune marque',
                    'heroicon-o-trophy', 'green'),
            ],
        ];
    }
}
