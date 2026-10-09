<?php

namespace App\Filament\Resources\Couriers\Widgets;

use App\Enums\Permission;
use App\Models\Courier;
use App\Services\Delivery\CourierFinances;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * A courier's money on their page (F-126): since the start, with this month underneath, and what they still owe.
 */
class CourierFinanceOverview extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected ?string $heading = 'Finances';

    protected ?string $pollingInterval = null;

    protected int|array|null $columns = 3;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewFinances->value);
    }

    protected function getStats(): array
    {
        /** @var Courier $courier */
        $courier = $this->record;
        $finances = app(CourierFinances::class);
        $all = $finances->statement($courier);
        $month = $finances->statement($courier, now()->startOfMonth(), now());
        $thisMonth = fn (string $value) => "Ce mois : {$value}";

        return [
            Stat::make('Livraisons réussies', (string) $all['delivered'])
                ->icon(Heroicon::OutlinedCheckCircle)
                ->description($thisMonth((string) $month['delivered'])),
            Stat::make('Commandes livrées', Money::format($all['orders_total']))
                ->icon(Heroicon::OutlinedShoppingBag)
                ->description($thisMonth(Money::format($month['orders_total']))),
            Stat::make('Frais de livraison générés', Money::format($all['shipping_fees']))
                ->icon(Heroicon::OutlinedTruck)
                ->description($thisMonth(Money::format($month['shipping_fees']))),
            Stat::make('Encaissé à la livraison', Money::format($all['collected']))
                ->icon(Heroicon::OutlinedBanknotes)
                ->description($thisMonth(Money::format($month['collected']))),
            Stat::make('Déjà reversé', Money::format($all['remitted']))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->description($thisMonth(Money::format($month['remitted']))),
            Stat::make('Reste à reverser', Money::format($all['due']))
                ->icon(Heroicon::OutlinedExclamationCircle)
                ->description($all['due'] > 0 ? 'Argent encore chez le livreur' : 'Rien à reverser')
                ->color($all['due'] > 0 ? 'warning' : 'success'),
        ];
    }
}
