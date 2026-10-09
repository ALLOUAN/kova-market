<?php

namespace App\Filament\Settings\Widgets;

use App\Filament\Pages\Maintenance;
use App\Filament\Support\ListHeroWidget;
use App\Models\Setting;
use App\Services\Payments\OnlinePayments;
use App\Services\Storefront\Maintenance as MaintenanceMode;

/**
 * Header of the store settings: what is set up and what is missing (online payment, e-mails, audience measurement,
 * Meta's Conversions API, maintenance), so that a gap shows at a glance. Kept out of app/Filament/Widgets so that the
 * dashboard does not pick it up.
 */
class SettingsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $payments = app(OnlinePayments::class)->isAvailable();
        $mailer = config('mail.default');
        $mail = ! in_array($mailer, ['log', 'array'], true);
        $trackers = array_keys(array_filter([
            'Meta' => Setting::get('analytics.meta_pixel_id'),
            'Google' => Setting::get('analytics.ga4_id'),
            'TikTok' => Setting::get('analytics.tiktok_pixel_id'),
        ]));
        $conversions = filled(Setting::get('analytics.meta_pixel_id')) && filled(config('services.meta.conversions_token'));
        $maintenance = app(MaintenanceMode::class)->enabled();

        return [
            'title' => 'Paramètres de la boutique',
            'icon' => 'heroicon-o-cog-6-tooth',
            'lead' => 'Réglages classés par onglet. Le bouton « Enregistrer », en bas, enregistre les changements de tous les onglets en une fois.',
            'kpis' => [
                self::kpi('Paiement en ligne', $payments ? 'Actif' : 'Inactif', $payments ? 'CinetPay : Mobile Money et carte' : 'Clés CinetPay absentes du fichier .env',
                    'heroicon-o-credit-card', $payments ? 'green' : 'red', null, $payments ? null : 'red'),
                self::kpi('E-mails', $mail ? 'Actifs' : 'Inactifs', $mail ? 'Envoyés depuis '.e((string) config('mail.from.address')) : 'Aucun serveur d’envoi configuré',
                    'heroicon-o-envelope', $mail ? 'green' : 'red', null, $mail ? null : 'red'),
                self::kpi('Mesure d’audience', $trackers === [] ? 'Aucune' : implode(', ', $trackers), $trackers === [] ? 'Onglet « Audience »' : 'Chargée après accord du visiteur',
                    'heroicon-o-chart-bar', $trackers === [] ? 'gold' : 'navy'),
                self::kpi('API Conversions Meta', $conversions ? 'Active' : 'Inactive', $conversions ? 'Ventes aussi envoyées par le serveur' : 'Pixel Meta et jeton nécessaires',
                    'heroicon-o-arrows-right-left', $conversions ? 'green' : 'gold'),
                self::kpi('Site', $maintenance ? 'En maintenance' : 'En ligne', $maintenance ? 'Les visiteurs voient la page de maintenance' : 'Ouvert à tous les visiteurs',
                    'heroicon-o-globe-alt', $maintenance ? 'red' : 'green', Maintenance::getUrl(), $maintenance ? 'red' : null),
            ],
        ];
    }
}
