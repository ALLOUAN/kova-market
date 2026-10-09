<?php

namespace App\Filament\Resources\Couriers\Widgets;

use App\Filament\Pages\DeliveryDashboard;
use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Courier;
use App\Models\Order;
use App\Services\Delivery\CashSettlement;
use App\Support\Money;

/**
 * Header of the couriers list: available, on a round, suspended, and the cash they still have to hand over.
 */
class CouriersOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $total = Courier::query()->count();
        $available = Courier::query()->available()->count();
        $busy = Courier::query()->available()->has('openOrders')->count();
        $suspended = $total - $available;
        $owed = (int) CashSettlement::pending(Order::query()->whereNotNull('courier_id'))->selectRaw(CashSettlement::owedSql().' as owed')->value('owed');
        $owing = CashSettlement::pending(Order::query()->whereNotNull('courier_id'))->distinct()->count('courier_id');
        $tab = fn (string $tab) => CourierResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Livreurs',
            'icon' => 'heroicon-o-truck',
            'lead' => '<strong>'.$available.'</strong> livreur'.($available > 1 ? 's' : '').' disponible'.($available > 1 ? 's' : '').', dont <strong>'.$busy.'</strong> en course.'
                .($owed > 0 ? ' <strong>'.e(Money::format($owed)).'</strong> encaissés restent à reverser.' : ''),
            'kpis' => [
                self::kpi('Disponibles', (string) $available, $total.' au total',
                    'heroicon-o-user-group', 'navy', $tab('available')),
                self::kpi('En course', (string) $busy, 'Avec au moins une commande en cours',
                    'heroicon-o-truck', 'orange', $tab('busy')),
                self::kpi('À reverser', Money::format($owed), $owing > 0 ? $owing.' livreur(s) doivent de l’argent' : 'Tout est reversé',
                    'heroicon-o-banknotes', 'gold', $tab('owing'), $owed > 0 ? 'gold' : null, money: true),
                self::kpi('Suspendus', (string) $suspended, 'Ne reçoivent plus de commandes',
                    'heroicon-o-no-symbol', $suspended > 0 ? 'red' : 'green', $tab('suspended')),
                self::kpi('Tableau de bord', 'Ouvrir', 'Commandes à attribuer, tournées, alertes',
                    'heroicon-o-map', 'green', DeliveryDashboard::getUrl()),
            ],
        ];
    }
}
