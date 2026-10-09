<?php

namespace App\Filament\Resources\Banners\Widgets;

use App\Enums\BannerPlacement;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Banner;

/**
 * Header of the home page banners: shown now (with the slots left empty), scheduled, expired and hidden.
 */
class BannersOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $live = Banner::query()->live()->count();
        $filled = Banner::query()->live()->distinct()->count('placement');
        $slots = count(BannerPlacement::cases());
        $scheduled = Banner::query()->where('is_visible', true)->where('starts_at', '>', now())->count();
        $expired = Banner::query()->where('ends_at', '<', now())->count();
        $hidden = Banner::query()->where('is_visible', false)->count();
        $tab = fn (string $tab) => BannerResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Bannières de l’accueil',
            'icon' => 'heroicon-o-photo',
            'lead' => '<strong>'.$live.'</strong> bannière'.($live > 1 ? 's' : '').' en ligne sur la page d’accueil, dans <strong>'.$filled.'</strong> emplacement'.($filled > 1 ? 's' : '').' sur '.$slots.'.',
            'kpis' => [
                self::kpi('En ligne', (string) $live, $filled < $slots ? ($slots - $filled).' emplacement(s) sans bannière' : 'Tous les emplacements sont remplis',
                    'heroicon-o-eye', 'green', $tab('live'), $filled < $slots ? 'gold' : null),
                self::kpi('Programmées', (string) $scheduled, 'Apparaîtront à leur date de début',
                    'heroicon-o-calendar-days', 'navy', $tab('scheduled')),
                self::kpi('Expirées', (string) $expired, 'Date de fin passée',
                    'heroicon-o-clock', 'gold', $tab('expired')),
                self::kpi('Masquées', (string) $hidden, '« Visible » décoché',
                    'heroicon-o-eye-slash', 'navy', $tab('hidden')),
            ],
        ];
    }
}
