<?php

namespace App\Filament\Widgets;

use App\Enums\Permission;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\Orders\SalesFigures;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Sales of the day against the day before, orders waiting for the team and the average basket, each with its trend
 * over the last 7 days (F-109).
 */
class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected ?string $heading = 'Aujourd’hui';

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageOrders->value);
    }

    protected function getStats(): array
    {
        $figures = app(SalesFigures::class);
        $week = $figures->daily(7);
        $today = [today(), now()];
        $yesterday = [today()->subDay(), today()->subSecond()];

        $revenue = $figures->revenue(...$today);
        $revenueBefore = $figures->revenue(...$yesterday);
        $orders = $figures->count(...$today);
        $ordersBefore = $figures->count(...$yesterday);
        $toHandle = $figures->toHandle();
        $month = [today()->subDays(29), now()];
        $monthCount = $figures->count(...$month);
        $basket = $monthCount > 0 ? intdiv($figures->revenue(...$month), $monthCount) : 0;

        return [
            $this->trend(Stat::make('Chiffre d’affaires', Money::format($revenue))->icon(Heroicon::OutlinedBanknotes), $revenue, $revenueBefore, Money::format($revenueBefore))
                ->chart($week->pluck('revenue')->all()),
            $this->trend(Stat::make('Commandes', (string) $orders)->icon(Heroicon::OutlinedShoppingBag), $orders, $ordersBefore, (string) $ordersBefore)
                ->chart($week->pluck('orders')->all()),
            Stat::make('À traiter', (string) $toHandle)
                ->icon(Heroicon::OutlinedClock)
                ->description('Reçues, confirmées, en préparation')
                ->color($toHandle > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl()),
            Stat::make('Panier moyen', Money::format($basket))
                ->icon(Heroicon::OutlinedShoppingCart)
                ->description('30 derniers jours, '.$monthCount.' '.($monthCount > 1 ? 'commandes' : 'commande')),
        ];
    }

    /** "Hier : 10 000 FCFA · +12 %", the change in green or red. */
    private function trend(Stat $stat, int $now, int $before, string $beforeLabel): Stat
    {
        if ($before === 0) {
            return $stat->description("Hier : {$beforeLabel}");
        }

        $change = (int) round(($now - $before) / $before * 100);

        return $stat->description("Hier : {$beforeLabel} · ".($change >= 0 ? '+' : '−').abs($change).' %')
            ->descriptionIcon($change >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
            ->color($change >= 0 ? 'success' : 'danger');
    }
}
