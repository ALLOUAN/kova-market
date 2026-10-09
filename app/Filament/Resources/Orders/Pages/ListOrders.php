<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Widgets\OrdersOverview;
use App\Models\Order;
use App\Services\Orders\SalesFigures;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /**
     * The header band (OrdersOverview) carries the title and the day in one sentence.
     */
    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderWidgets(): array
    {
        return [OrdersOverview::class];
    }

    /**
     * One click to the orders of a step; the figures of the header band open these tabs.
     */
    public function getTabs(): array
    {
        $status = fn (OrderStatus ...$statuses) => fn (Builder $query) => $query->whereIn('status', $statuses);
        $count = fn (OrderStatus ...$statuses) => fn () => Order::query()->whereIn('status', $statuses)->count() ?: null;

        // Same rule as the dashboard: an online order still waiting for its payment is not to handle yet.
        $toHandle = fn (Builder $query) => $query->whereIn('status', SalesFigures::TO_HANDLE)
            ->where(fn (Builder $query) => $query->where('payment_method', PaymentMethod::CashOnDelivery)->orWhere('payment_status', PaymentStatus::Paid));

        return [
            'all' => Tab::make('Toutes'),
            'to_handle' => Tab::make('À traiter')->modifyQueryUsing($toHandle)->badge(fn () => app(SalesFigures::class)->toHandle() ?: null)->badgeColor('warning'),
            'on_the_way' => Tab::make('En route')->modifyQueryUsing($status(OrderStatus::Shipped, OrderStatus::OutForDelivery))->badge($count(OrderStatus::Shipped, OrderStatus::OutForDelivery))->badgeColor('info'),
            'delivered' => Tab::make('Livrées')->modifyQueryUsing($status(OrderStatus::Delivered)),
            'cancelled' => Tab::make('Annulées')->modifyQueryUsing($status(OrderStatus::Cancelled)),
        ];
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

    /**
     * The orders shown (filters, search, sort) with their amounts, payment and delivery, for a spreadsheet.
     * Semicolons and a BOM so that Excel opens it as is.
     */
    private function export(): StreamedResponse
    {
        $query = $this->getFilteredSortedTableQuery()->with('courier.user');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['Commande', 'Date', 'Client', 'Téléphone', 'Zone', 'Destination', 'Produits (FCFA)', 'Livraison (FCFA)', 'Total (FCFA)', 'Mode de paiement', 'Paiement', 'Statut', 'Livreur', 'Encaissé par le livreur (FCFA)', 'Reversé (FCFA)'], ';');

            $query->lazy()->each(fn (Order $order) => fputcsv($out, [
                $order->number,
                $order->created_at->format('d/m/Y H:i'),
                $order->customer_name,
                $order->phone,
                $order->zone_name,
                $order->destinationLabel(),
                $order->total - $order->shipping_fee,
                $order->shipping_fee,
                $order->total,
                $order->payment_method->getLabel(),
                $order->payment_status->getLabel(),
                $order->status->getLabel(),
                $order->courier?->name(),
                $order->cash_collected,
                $order->cash_collected === null ? null : $order->cash_remitted,
            ], ';'));

            fclose($out);
        }, 'commandes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
