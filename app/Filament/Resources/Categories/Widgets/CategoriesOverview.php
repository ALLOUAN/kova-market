<?php

namespace App\Filament\Resources\Categories\Widgets;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Category;
use App\Models\Product;

/**
 * Header of the three category lists (all, parent categories, sub-categories): the tree in one sentence, then
 * departments, sub-categories, featured on the home page and empty ones, each card opening the matching list.
 */
class CategoriesOverview extends ListHeroWidget
{
    /** Which list it heads: "all", "parents" or "sub". */
    public string $scope = 'all';

    protected function hero(): array
    {
        $roots = Category::query()->whereNull('parent_id')->count();
        $subs = Category::query()->whereNotNull('parent_id')->count();
        $featured = Category::query()->where('is_featured', true)->count();
        $empty = Category::query()->doesntHave('products')->doesntHave('children')->count();
        $products = Product::query()->where('is_active', true)->count();
        $biggest = Category::query()->withCount('products')->orderByDesc('products_count')->first();

        [$title, $icon, $lead] = match ($this->scope) {
            'parents' => ['Catégories parentes', 'heroicon-o-rectangle-stack',
                '<strong>'.$roots.'</strong> rayon'.($roots > 1 ? 's' : '').' principaux, dans l’ordre des menus de la boutique. Glissez les lignes pour le changer.'],
            'sub' => ['Sous-catégories', 'heroicon-o-squares-2x2',
                '<strong>'.$subs.'</strong> sous-catégorie'.($subs > 1 ? 's' : '').', rangées par catégorie parente.'],
            default => ['Catégories', 'heroicon-o-tag',
                '<strong>'.$roots.'</strong> rayon'.($roots > 1 ? 's' : '').' et <strong>'.$subs.'</strong> sous-catégorie'.($subs > 1 ? 's' : '')
                .($empty > 0 ? ', dont <strong>'.$empty.'</strong> encore vide'.($empty > 1 ? 's' : '').'.' : '.')],
        };

        return [
            'title' => $title,
            'icon' => $icon,
            'lead' => $lead,
            'kpis' => [
                self::kpi('Rayons principaux', (string) $roots, 'Dans le menu « Boutique »',
                    'heroicon-o-rectangle-stack', 'navy', CategoryResource::getUrl('parents')),
                self::kpi('Sous-catégories', (string) $subs, $roots > 0 ? 'Environ '.number_format($subs / $roots, 1, ',', '').' par rayon' : 'Aucun rayon',
                    'heroicon-o-squares-2x2', 'orange', CategoryResource::getUrl('sub')),
                self::kpi('À l’accueil', (string) $featured, 'Mises en avant sur la page d’accueil',
                    'heroicon-o-home', 'gold', CategoryResource::getUrl('index', ['tab' => 'featured'])),
                self::kpi('Sans produit', (string) $empty, $empty > 0 ? 'À remplir ou à supprimer' : 'Toutes les catégories ont des produits',
                    'heroicon-o-inbox', $empty > 0 ? 'red' : 'green', CategoryResource::getUrl('index', ['tab' => 'empty']), $empty > 0 ? 'gold' : null),
                self::kpi('La plus fournie', $biggest ? (string) $biggest->products_count : '—', $biggest ? e($biggest->name).' · '.$products.' produit'.($products > 1 ? 's' : '').' en ligne au total' : 'Aucune catégorie',
                    'heroicon-o-trophy', 'green'),
            ],
        ];
    }
}
