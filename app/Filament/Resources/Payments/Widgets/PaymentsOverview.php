<?php

namespace App\Filament\Resources\Payments\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Figures of the payments space: takings received online, success rate, payments still open and refunds owed.
 */
class PaymentsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $paid = fn () => Payment::query()->whereIn('status', [TransactionStatus::Succeeded, TransactionStatus::Refunded]);

        $today = $paid()->where('paid_at', '>=', today());
        $month = $paid()->where('paid_at', '>=', now()->startOfMonth());

        $closed = Payment::query()->where('created_at', '>=', now()->subDays(30))
            ->whereNotIn('status', [TransactionStatus::Initiated, TransactionStatus::Pending]);
        $attempts = (clone $closed)->count();
        $succeeded = (clone $closed)->whereIn('status', [TransactionStatus::Succeeded, TransactionStatus::Refunded])->count();
        $rate = $attempts > 0 ? (int) round($succeeded / $attempts * 100) : null;

        $open = Payment::query()->whereIn('status', [TransactionStatus::Initiated, TransactionStatus::Pending])->count();
        $toRefund = Payment::query()->toRefund();
        $toRefundCount = (clone $toRefund)->count();

        return [
            Stat::make('Encaissé aujourd’hui', Money::format((int) (clone $today)->sum('amount')))
                ->description((clone $today)->count().' paiement(s)'),
            Stat::make('Encaissé ce mois', Money::format((int) (clone $month)->sum('amount')))
                ->description((clone $month)->count().' paiement(s)'),
            Stat::make('Taux de réussite', $rate === null ? '—' : "{$rate} %")
                ->description("30 derniers jours : {$succeeded} sur {$attempts} tentative(s)")
                ->color($rate === null ? 'gray' : ($rate >= 60 ? 'success' : 'warning')),
            Stat::make('En cours', $open)
                ->description('En attente de la réponse de CinetPay')
                ->color($open > 0 ? 'warning' : 'gray'),
            Stat::make('À rembourser', $toRefundCount)
                ->description($toRefundCount > 0
                    ? Money::format((int) (clone $toRefund)->sum('amount')).' reçus sur des commandes annulées'
                    : 'Aucun paiement sur une commande annulée')
                ->color($toRefundCount > 0 ? 'danger' : 'success'),
        ];
    }
}
