<?php

namespace App\Filament\Settings\Widgets;

use App\Filament\Pages\Menus;
use App\Filament\Support\ListHeroWidget;
use App\Models\Setting;
use App\Services\Storefront\ConfigOverrides;

/**
 * Header of the site menus: how many links each menu holds, and whether they were changed from the original ones.
 */
class MenusOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $menus = Menus::current();
        $links = fn (array $groups) => collect($groups)->sum(fn (array $group) => count($group['links'] ?? []));
        $customised = Setting::query()->whereIn('key', ConfigOverrides::MENUS)->whereNotNull('value')->exists();

        return [
            'title' => 'Menus du site',
            'icon' => 'heroicon-o-bars-3',
            'lead' => 'Les liens de l’en-tête, du pied de page et du panneau des catégories. '
                .($customised ? 'Vous avez <strong>personnalisé</strong> ces menus.' : 'Ce sont les <strong>menus d’origine</strong> du site.'),
            'kpis' => [
                self::kpi('Menu « Pages »', (string) $links($menus['pages']), count($menus['pages']).' colonne(s) dans l’en-tête',
                    'heroicon-o-squares-2x2', 'navy'),
                self::kpi('Menu « Aide »', (string) count($menus['help']), 'Liens du menu déroulant',
                    'heroicon-o-lifebuoy', 'orange'),
                self::kpi('Pied de page', (string) $links($menus['footer']), count($menus['footer']).' colonne(s) · '.count($menus['legal']).' lien(s) légaux',
                    'heroicon-o-queue-list', 'gold'),
                self::kpi('Panneau des catégories', (string) $links($menus['sidebar']), count($menus['sidebar']).' groupe(s)',
                    'heroicon-o-bars-3-bottom-left', 'green'),
            ],
        ];
    }
}
