<?php

namespace App\Filament\Resources\Collections\Widgets;

use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\Collections\CollectionStatus;
use App\Filament\Support\ListHeroWidget;
use App\Models\Collection;

/**
 * Header of the home page selections ("Offres du jour"…): running, scheduled, offer over, empty, each card opening
 * the matching tab.
 */
class CollectionsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $total = Collection::query()->count();
        $running = CollectionStatus::running(Collection::query())->count();
        $scheduled = CollectionStatus::scheduled(Collection::query())->count();
        $over = CollectionStatus::over(Collection::query())->count();
        $empty = Collection::query()->doesntHave('products')->count();
        $endingSoon = CollectionStatus::running(Collection::query())->whereNotNull('ends_at')->where('ends_at', '<=', now()->addDays(2))->count();
        $tab = fn (string $tab) => CollectionResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Sélections',
            'icon' => 'heroicon-o-sparkles',
            'lead' => 'Les sélections de produits de la page d’accueil : <strong>'.$running.'</strong> en cours'
                .($scheduled > 0 ? ', <strong>'.$scheduled.'</strong> programmée'.($scheduled > 1 ? 's' : '') : '').'.',
            'kpis' => [
                self::kpi('En cours', (string) $running, $endingSoon > 0 ? "<strong>{$endingSoon}</strong> se termine(nt) dans 48 h" : 'Visibles sur la boutique',
                    'heroicon-o-play-circle', 'green', $tab('running'), $endingSoon > 0 ? 'orange' : null),
                self::kpi('Programmées', (string) $scheduled, 'Apparaîtront à leur date de début',
                    'heroicon-o-calendar-days', 'navy', $tab('scheduled')),
                self::kpi('Offre terminée', (string) $over, $over > 0 ? 'Changez la date ou les produits' : 'Aucune offre expirée',
                    'heroicon-o-clock', 'gold', $tab('over'), $over > 0 ? 'gold' : null),
                self::kpi('Sans produit', (string) $empty, $empty > 0 ? 'Rien ne s’affiche pour elles' : $total.' sélection(s) remplie(s)',
                    'heroicon-o-inbox', $empty > 0 ? 'red' : 'green', $tab('empty'), $empty > 0 ? 'red' : null),
            ],
        ];
    }
}
