<?php

namespace App\Filament\Resources\Bundles\Widgets;

use App\Filament\Resources\Bundles\BundleResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Product;

/**
 * Header of the packs list: how many, online, sold out and sold, each card opening the matching tab.
 */
class BundlesOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $packs = fn () => Product::query()->where('is_bundle', true);
        $total = $packs()->count();
        $online = $packs()->where('is_active', true)->count();
        $soldOut = $packs()->where('stock', 0)->count();
        $sold = (int) $packs()->sum('sold_count');
        $best = $packs()->where('sold_count', '>', 0)->orderByDesc('sold_count')->first(['name', 'sold_count']);
        $tab = fn (string $tab) => BundleResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Packs',
            'icon' => 'heroicon-o-gift',
            'lead' => 'Plusieurs produits vendus ensemble à un prix avantageux : <strong>'.$online.'</strong> pack'.($online > 1 ? 's' : '').' en ligne sur '.$total.'.',
            'kpis' => [
                self::kpi('En ligne', (string) $online, ($total - $online).' hors ligne',
                    'heroicon-o-eye', 'navy', $tab('online')),
                self::kpi('En rupture', (string) $soldOut, $soldOut > 0 ? 'Un produit du pack manque' : 'Tous disponibles',
                    'heroicon-o-x-circle', $soldOut > 0 ? 'red' : 'green', $tab('sold_out'), $soldOut > 0 ? 'red' : null),
                self::kpi('Packs vendus', (string) $sold, $best ? 'Le plus vendu : '.e($best->name) : 'Aucune vente pour le moment',
                    'heroicon-o-shopping-bag', 'orange'),
            ],
        ];
    }
}
