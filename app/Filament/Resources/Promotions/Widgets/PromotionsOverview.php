<?php

namespace App\Filament\Resources\Promotions\Widgets;

use App\Filament\Resources\Promotions\PromotionResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Promotion;

/**
 * Header of the special offers (cards of the "Offres spéciales" panel): shown now, coming, over and hidden.
 */
class PromotionsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $now = Promotion::query()->where('is_visible', true)->where('ends_at', '>=', now())
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->count();
        $coming = Promotion::query()->where('is_visible', true)->where('starts_at', '>', now())->count();
        $over = Promotion::query()->where('ends_at', '<', now())->count();
        $hidden = Promotion::query()->where('is_visible', false)->count();
        $endingSoon = Promotion::query()->where('is_visible', true)->whereBetween('ends_at', [now(), now()->addDays(2)])->count();
        $tab = fn (string $tab) => PromotionResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Offres spéciales',
            'icon' => 'heroicon-o-megaphone',
            'lead' => 'Les cartes du panneau « Offres spéciales » de la boutique : <strong>'.$now.'</strong> affichée'.($now > 1 ? 's' : '').' en ce moment. Chaque carte disparaît après sa date de fin.',
            'kpis' => [
                self::kpi('En cours', (string) $now, $endingSoon > 0 ? "<strong>{$endingSoon}</strong> se termine(nt) dans 48 h" : 'Visibles sur la boutique',
                    'heroicon-o-play-circle', 'green', $tab('current'), $endingSoon > 0 ? 'orange' : null),
                self::kpi('À venir', (string) $coming, 'Apparaîtront à leur date de début',
                    'heroicon-o-calendar-days', 'navy', $tab('coming')),
                self::kpi('Terminées', (string) $over, 'Plus affichées',
                    'heroicon-o-clock', 'gold', $tab('over')),
                self::kpi('Masquées', (string) $hidden, '« Visible » décoché',
                    'heroicon-o-eye-slash', 'navy', $tab('hidden')),
            ],
        ];
    }
}
