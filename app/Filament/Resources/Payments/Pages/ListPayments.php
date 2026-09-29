<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Widgets\PaymentsOverview;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [PaymentsOverview::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    public function getTabs(): array
    {
        $status = fn (TransactionStatus ...$statuses) => fn (Builder $query) => $query->whereIn('status', $statuses);

        return [
            'all' => Tab::make('Tous'),
            'succeeded' => Tab::make('Réussis')->modifyQueryUsing($status(TransactionStatus::Succeeded)),
            'open' => Tab::make('En cours')->modifyQueryUsing($status(TransactionStatus::Initiated, TransactionStatus::Pending)),
            'failed' => Tab::make('Échoués ou annulés')->modifyQueryUsing($status(TransactionStatus::Failed, TransactionStatus::Cancelled)),
            'refunded' => Tab::make('Remboursés')->modifyQueryUsing($status(TransactionStatus::Refunded)),
            'to_refund' => Tab::make('À rembourser')
                ->modifyQueryUsing(fn (Builder $query) => $query->toRefund())
                ->badge(fn () => Payment::query()->toRefund()->count() ?: null)
                ->badgeColor('danger'),
        ];
    }

    /**
     * The payments shown (tab, filters, search), to reconcile with CinetPay's statements. Semicolons and a BOM so
     * that Excel opens it as is.
     */
    private function export(): StreamedResponse
    {
        $query = $this->getFilteredSortedTableQuery()->with('order');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['Lancé le', 'Commande', 'Client', 'Téléphone', 'Montant (FCFA)', 'Statut', 'Moyen', 'Référence KOVA', 'Référence CinetPay', 'Payé le', 'Remboursé le', 'Motif'], ';');

            $query->lazy()->each(fn (Payment $payment) => fputcsv($out, [
                $payment->created_at->format('d/m/Y H:i'),
                $payment->order?->number,
                $payment->order?->customer_name,
                $payment->payer_phone,
                $payment->amount,
                $payment->status->getLabel(),
                $payment->operator,
                $payment->merchant_transaction_id,
                $payment->gateway_transaction_id,
                $payment->paid_at?->format('d/m/Y H:i'),
                $payment->refunded_at?->format('d/m/Y H:i'),
                $payment->refund_reason ?? $payment->failure_reason,
            ], ';'));

            fclose($out);
        }, 'paiements-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
