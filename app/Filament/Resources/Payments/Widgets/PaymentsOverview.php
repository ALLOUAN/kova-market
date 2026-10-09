<?php

namespace App\Filament\Resources\Payments\Widgets;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\ListHeroWidget;
use App\Models\Payment;
use App\Support\Money;

/**
 * Header of the payments space: takings received online today and this month, success rate, payments still open
 * and refunds owed, each card opening the matching tab.
 */
class PaymentsOverview extends ListHeroWidget
{
    protected function hero(): array
    {
        $paid = fn () => Payment::query()->whereIn('status', [TransactionStatus::Succeeded, TransactionStatus::Refunded]);

        $today = $paid()->where('paid_at', '>=', today());
        $yesterday = (int) $paid()->whereBetween('paid_at', [today()->subDay(), today()->subSecond()])->sum('amount');
        $month = $paid()->where('paid_at', '>=', now()->startOfMonth());
        $todayAmount = (int) (clone $today)->sum('amount');

        $closed = Payment::query()->where('created_at', '>=', now()->subDays(30))
            ->whereNotIn('status', [TransactionStatus::Initiated, TransactionStatus::Pending]);
        $attempts = (clone $closed)->count();
        $succeeded = (clone $closed)->whereIn('status', [TransactionStatus::Succeeded, TransactionStatus::Refunded])->count();
        $rate = $attempts > 0 ? (int) round($succeeded / $attempts * 100) : null;

        $open = Payment::query()->whereIn('status', [TransactionStatus::Initiated, TransactionStatus::Pending])->count();
        $toRefund = Payment::query()->toRefund();
        $toRefundCount = (clone $toRefund)->count();
        $tab = fn (string $tab) => PaymentResource::getUrl('index', ['tab' => $tab]);

        return [
            'title' => 'Paiements en ligne',
            'icon' => 'heroicon-o-credit-card',
            'lead' => 'CinetPay : <strong>'.e(Money::format($todayAmount)).'</strong> encaissés aujourd’hui'
                .($toRefundCount > 0 ? ', et <strong>'.$toRefundCount.'</strong> remboursement'.($toRefundCount > 1 ? 's' : '').' à faire.' : '. Aucun remboursement en attente.'),
            'kpis' => [
                self::kpi('Encaissé aujourd’hui', Money::format($todayAmount), (clone $today)->count().' paiement(s) · hier '.e(Money::format($yesterday)).self::trend($todayAmount, $yesterday),
                    'heroicon-o-banknotes', 'orange', $tab('succeeded'), money: true),
                self::kpi('Encaissé ce mois', Money::format((int) (clone $month)->sum('amount')), (clone $month)->count().' paiement(s)',
                    'heroicon-o-calendar-days', 'navy', $tab('succeeded'), money: true),
                self::kpi('Taux de réussite', $rate === null ? '—' : "{$rate} %", "30 derniers jours : {$succeeded} sur {$attempts} tentative(s)",
                    'heroicon-o-check-badge', 'green', $tab('failed')),
                self::kpi('En cours', (string) $open, 'En attente de la réponse de CinetPay',
                    'heroicon-o-clock', 'gold', $tab('open'), $open > 0 ? 'gold' : null),
                self::kpi('À rembourser', (string) $toRefundCount, $toRefundCount > 0
                    ? e(Money::format((int) (clone $toRefund)->sum('amount'))).' reçus sur des commandes annulées'
                    : 'Aucun paiement sur une commande annulée',
                    'heroicon-o-receipt-refund', $toRefundCount > 0 ? 'red' : 'green', $tab('to_refund'), $toRefundCount > 0 ? 'red' : null),
            ],
        ];
    }
}
