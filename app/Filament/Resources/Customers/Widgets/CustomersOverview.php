<?php

namespace App\Filament\Resources\Customers\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Customer;
use App\Support\Money;

/**
 * Header of the customers list: how many, with an account or as guests, new this month, loyal (two orders or
 * more) and what they spent, each card opening the matching tab.
 */
class CustomersOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $total = Customer::query()->count();
        $accounts = Customer::query()->whereNotNull('user_id')->count();
        $newThisMonth = Customer::query()->where('created_at', '>=', now()->startOfMonth())->count();
        $newLastMonth = Customer::query()->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()->subSecond()])->count();
        $buyers = Customer::query()->where('orders_count', '>', 0)->count();
        $loyal = Customer::query()->where('orders_count', '>=', 2)->count();
        $spent = (int) Customer::query()->sum('total_spent');
        $tab = fn (string $tab) => CustomerResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Clients',
            'icon' => 'heroicon-o-users',
            'lead' => '<strong>'.$total.'</strong> client'.($total > 1 ? 's' : '').', dont <strong>'.$newThisMonth.'</strong> nouveau'.($newThisMonth > 1 ? 'x' : '').' ce mois-ci.',
            'kpis' => [
                self::kpi('Clients', (string) $total, "{$accounts} avec un compte · ".($total - $accounts).' invité(s)',
                    'heroicon-o-users', 'navy', $tab('all')),
                self::kpi('Nouveaux ce mois', (string) $newThisMonth, 'Mois dernier : '.$newLastMonth.self::trend($newThisMonth, $newLastMonth),
                    'heroicon-o-user-plus', 'orange', $tab('new')),
                self::kpi('Clients fidèles', (string) $loyal, $buyers > 0 ? round($loyal / $buyers * 100).' % des acheteurs ont commandé 2 fois ou plus' : '2 commandes ou plus',
                    'heroicon-o-heart', 'green', $tab('loyal')),
                self::kpi('Dépensé au total', Money::format($spent), $buyers > 0 ? 'Soit '.e(Money::format(intdiv($spent, $buyers))).' par acheteur' : 'Aucun achat pour le moment',
                    'heroicon-o-banknotes', 'gold', null, money: true),
            ],
        ];
    }
}
