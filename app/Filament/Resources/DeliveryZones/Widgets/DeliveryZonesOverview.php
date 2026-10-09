<?php

namespace App\Filament\Resources\DeliveryZones\Widgets;

use App\Filament\Resources\DeliveryZones\DeliveryZoneResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Commune;
use App\Models\DeliveryZone;
use App\Support\Money;

/**
 * Header of the delivery zones: active zones and communes served, fees, free delivery and zones without a courier.
 */
class DeliveryZonesOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $active = DeliveryZone::query()->where('is_active', true);
        $zones = (clone $active)->count();
        $inactive = DeliveryZone::query()->where('is_active', false)->count();
        $communes = Commune::query()->whereHas('zone', fn ($zone) => $zone->where('is_active', true))->count();
        $minFee = (int) (clone $active)->min('fee');
        $maxFee = (int) (clone $active)->max('fee');
        $free = (clone $active)->whereNotNull('free_shipping_threshold')->count();
        $noCourier = (clone $active)->doesntHave('couriers')->count();
        $tab = fn (string $tab) => DeliveryZoneResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Zones de livraison',
            'icon' => 'heroicon-o-map',
            'lead' => '<strong>'.$zones.'</strong> zone'.($zones > 1 ? 's' : '').' active'.($zones > 1 ? 's' : '').' couvrant <strong>'.$communes.'</strong> commune'.($communes > 1 ? 's' : '').'. Le client choisit sa commune, la zone fixe les frais et le délai.',
            'kpis' => [
                self::kpi('Zones actives', (string) $zones, $inactive.' désactivée(s)',
                    'heroicon-o-map', 'navy', $tab('active')),
                self::kpi('Communes desservies', (string) $communes, 'Proposées au client à la commande',
                    'heroicon-o-map-pin', 'orange'),
                self::kpi('Frais de livraison', $zones > 0 ? ($minFee === $maxFee ? Money::format($minFee) : Money::format($minFee).' – '.Money::format($maxFee)) : '—', 'Du moins cher au plus cher',
                    'heroicon-o-banknotes', 'gold', null, money: true),
                self::kpi('Livraison offerte', (string) $free, 'Zone(s) avec un seuil de gratuité',
                    'heroicon-o-gift', 'green', $tab('free_shipping')),
                self::kpi('Sans livreur', (string) $noCourier, $noCourier > 0 ? 'Attribuez-leur un livreur' : 'Toutes ont un livreur',
                    'heroicon-o-exclamation-triangle', $noCourier > 0 ? 'red' : 'green', $tab('no_courier'), $noCourier > 0 ? 'red' : null),
            ],
        ];
    }
}
