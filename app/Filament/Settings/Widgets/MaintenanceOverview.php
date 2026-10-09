<?php

namespace App\Filament\Settings\Widgets;

use App\Filament\Support\ListHeroWidget;
use App\Services\Storefront\Maintenance as MaintenanceMode;

/**
 * Header of the maintenance page: the site's state, progress, expected return and the addresses let through.
 */
class MaintenanceOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $maintenance = app(MaintenanceMode::class);
        $on = $maintenance->enabled();
        $back = $on ? $maintenance->expectedBackAt() : null;
        $ips = count($maintenance->allowedIps());

        return [
            'title' => 'Mode maintenance',
            'icon' => $on ? 'heroicon-o-pause-circle' : 'heroicon-o-globe-alt',
            'lead' => $on
                ? 'Le site est <strong>en maintenance</strong>'.($maintenance->startedAt() ? ' depuis le '.$maintenance->startedAt()->translatedFormat('j F à H:i') : '').'. Votre équipe connectée au back-office continue de voir le site normalement.'
                : 'Le site est <strong>en ligne</strong>. Activez la maintenance pour fermer temporairement la boutique aux visiteurs ; votre équipe continue de la voir.',
            'kpis' => [
                self::kpi('Statut', $on ? 'En maintenance' : 'En ligne', $on ? 'Les visiteurs voient la page de maintenance' : 'Tous les visiteurs ont accès au site',
                    $on ? 'heroicon-o-pause-circle' : 'heroicon-o-check-circle', $on ? 'red' : 'green', null, $on ? 'red' : null),
                self::kpi('Progression', $maintenance->progress().' %', 'Affichée sur la page de maintenance',
                    'heroicon-o-arrow-trending-up', 'orange'),
                self::kpi('Durée estimée', $maintenance->durationLabel(), $back ? 'Retour prévu le '.$back->translatedFormat('j F à H:i') : 'Annoncée aux visiteurs',
                    'heroicon-o-clock', 'gold'),
                self::kpi('IP autorisées', (string) $ips, $ips > 0 ? 'Voient le site malgré la maintenance' : 'Seule l’équipe connectée voit le site',
                    'heroicon-o-shield-check', 'navy'),
            ],
        ];
    }
}
