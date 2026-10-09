<?php

namespace App\Services\Delivery;

use App\Enums\OrderStatus;
use App\Models\Courier;
use App\Models\CourierRemittance;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A courier's money (F-126): what they delivered and collected, and what they handed over. Deliveries count on
 * the day they were delivered (status history), payments on the day the store received them. "Due" is always
 * today's balance, whatever the period.
 */
class CourierFinances
{
    public function __construct(private CashSettlement $settlement) {}

    /**
     * @return array{delivered: int, orders_total: int, collected: int, shipping_fees: int, remitted: int, due: int}
     */
    public function statement(Courier $courier, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $delivered = $this->delivered($courier, $from, $to)
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as orders_total, COALESCE(SUM(cash_collected), 0) as collected, COALESCE(SUM(shipping_fee), 0) as shipping_fees')
            ->toBase()
            ->first();

        $remitted = $courier->remittances()->valid()
            ->when($from, fn (Builder $query) => $query->where('received_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('received_at', '<=', $to))
            ->sum('amount');

        return [
            'delivered' => (int) $delivered->orders,
            'orders_total' => (int) $delivered->orders_total,
            'collected' => (int) $delivered->collected,
            'shipping_fees' => (int) $delivered->shipping_fees,
            'remitted' => (int) $remitted,
            'due' => $this->settlement->due($courier),
        ];
    }

    /**
     * Latest money movements, newest first: cash collected at a delivery (+), payments to the store (−),
     * cancelled payments shown for the record.
     *
     * @return Collection<int, array{date: CarbonInterface, type: string, label: string, amount: int, cancelled: bool, note: ?string}>
     */
    public function history(Courier $courier, int $limit = 30): Collection
    {
        $deliveries = $this->delivered($courier)
            ->whereNotNull('cash_collected')
            ->addSelect(['delivered_at' => self::deliveredAtQuery()])
            ->orderByDesc('delivered_at')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order) => [
                'date' => $order->delivered_at ? Carbon::parse($order->delivered_at) : $order->updated_at,
                'type' => 'encaissement',
                'label' => "Encaissé à la livraison de {$order->number}",
                'amount' => $order->cash_collected,
                'cancelled' => false,
                'note' => $order->cash_note,
            ]);

        $remittances = $courier->remittances()->with('receivedBy')->latest('received_at')->limit($limit)->get()
            ->map(fn (CourierRemittance $remittance) => [
                'date' => $remittance->received_at,
                'type' => 'versement',
                'label' => "Versement {$remittance->number()} · {$remittance->method->getLabel()}".($remittance->receivedBy ? " · reçu par {$remittance->receivedBy->name}" : ''),
                'amount' => -$remittance->amount,
                'cancelled' => $remittance->isCancelled(),
                'note' => $remittance->isCancelled() ? "Annulé : {$remittance->cancel_reason}" : $remittance->note,
            ]);

        return $deliveries->concat($remittances)->sortByDesc('date')->take($limit)->values();
    }

    /**
     * The courier's delivered orders, delivered within the period when one is given.
     */
    private function delivered(Courier $courier, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Builder
    {
        return Order::query()
            ->whereBelongsTo($courier)
            ->where('status', OrderStatus::Delivered)
            ->when($from || $to, fn (Builder $query) => $query->whereHas('statusHistory', fn (Builder $history) => $history
                ->where('to_status', OrderStatus::Delivered)
                ->when($from, fn (Builder $history) => $history->where('created_at', '>=', $from))
                ->when($to, fn (Builder $history) => $history->where('created_at', '<=', $to))));
    }

    private static function deliveredAtQuery(): QueryBuilder
    {
        return DB::table('order_status_histories')
            ->selectRaw('MAX(created_at)')
            ->whereColumn('order_status_histories.order_id', 'orders.id')
            ->where('to_status', OrderStatus::Delivered->value);
    }
}
