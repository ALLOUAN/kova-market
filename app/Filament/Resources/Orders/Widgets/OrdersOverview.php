<?php

namespace App\Filament\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Order;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Orders\SalesFigures;
use App\Support\Money;

/**
 * Header of the orders list: the day in one sentence, then what to handle, on the way, delivered today and the
 * day's sales (for those who see them), each card opening the matching tab.
 */
class OrdersOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $sales = app(SalesFigures::class);
        $toHandle = $sales->toHandle();
        $onTheWay = Order::query()->whereIn('status', [OrderStatus::Shipped, OrderStatus::OutForDelivery])->count();
        $deliveredToday = Order::query()->where('status', OrderStatus::Delivered)
            ->whereHas('statusHistory', fn ($history) => $history->where('to_status', OrderStatus::Delivered)->where('created_at', '>=', today()))
            ->count();
        $unassigned = app(DeliveryDispatcher::class)->unassigned()->count();
        $placedToday = Order::query()->where('created_at', '>=', today())->count();
        $tab = fn (string $tab) => OrderResource::getUrl('index', ['tab' => $tab]);

        $kpis = [
            self::kpi('À traiter', (string) $toHandle, 'Reçues, confirmées, en préparation',
                'heroicon-o-clock', 'orange', $tab('to_handle'), $toHandle > 0 ? 'orange' : null),
            self::kpi('En route', (string) $onTheWay, $unassigned > 0 ? "<strong>{$unassigned}</strong> sans livreur" : 'Expédiées ou en livraison',
                'heroicon-o-truck', 'navy', $tab('on_the_way')),
            self::kpi('Livrées aujourd’hui', (string) $deliveredToday, 'Remises au client',
                'heroicon-o-check-circle', 'green', $tab('delivered')),
        ];

        if (auth()->user()?->can(Permission::ManageOrders->value)) {
            $revenue = $sales->revenue(today(), now());
            $before = $sales->revenue(today()->subDay(), today()->subSecond());
            $kpis[] = self::kpi('Ventes du jour', Money::format($revenue), $sales->count(today(), now()).' vente(s) · hier '.e(Money::format($before)).self::trend($revenue, $before),
                'heroicon-o-banknotes', 'gold', null, money: true);
        }

        return [
            'title' => 'Commandes',
            'icon' => 'heroicon-o-shopping-bag',
            'lead' => '<strong>'.$placedToday.'</strong> commande'.($placedToday > 1 ? 's' : '').' aujourd’hui'
                .($toHandle > 0 ? ', <strong>'.$toHandle.'</strong> à traiter.' : '. Tout est traité.'),
            'kpis' => $kpis,
        ];
    }
}
