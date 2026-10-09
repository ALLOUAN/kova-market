<?php

namespace App\Services\Finance;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Models\Courier;
use App\Models\CourierRemittance;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Delivery\CashSettlement;
use App\Services\Orders\SalesFigures;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Figures of the finance dashboard (lot 3), one definition for the page and its export:
 *  - sales and revenue are those of the dashboard (SalesFigures): orders placed in the period, not cancelled, not an
 *    online order still unpaid; revenue = order totals, split into products and delivery fees;
 *  - online takings: CinetPay payments received in the period minus the refunds made in the period;
 *  - takings on delivery: cash collected at the deliveries made in the period (delivery date);
 *  - cash with the couriers and their payments: today's balance, and the payments received in the period.
 * No courier commission: the platform pays none for now, delivery fees are the store's.
 */
class FinanceReport
{
    public function __construct(private SalesFigures $sales) {}

    /**
     * @return array<string, int>
     */
    public function summary(FinancePeriod $period): array
    {
        $placed = Order::query()->whereBetween('created_at', [$period->from, $period->to]);
        $sales = $this->sales->sales()->whereBetween('created_at', [$period->from, $period->to])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue, COALESCE(SUM(shipping_fee), 0) as fees')
            ->toBase()
            ->first();

        $online = (int) Payment::query()
            ->whereIn('status', [TransactionStatus::Succeeded, TransactionStatus::Refunded])
            ->whereBetween('paid_at', [$period->from, $period->to])
            ->sum('amount');
        $refunds = (int) Payment::query()
            ->where('status', TransactionStatus::Refunded)
            ->whereBetween('refunded_at', [$period->from, $period->to])
            ->sum('amount');
        $onDelivery = (int) $this->deliveredIn($period)->sum('cash_collected');

        return [
            'orders' => (clone $placed)->count(),
            'delivered' => (clone $placed)->where('status', OrderStatus::Delivered)->count(),
            'in_progress' => (clone $placed)->whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])->count(),
            'cancelled' => (clone $placed)->where('status', OrderStatus::Cancelled)->count(),
            'sales' => (int) $sales->orders,
            'revenue' => (int) $sales->revenue,
            'products' => (int) $sales->revenue - (int) $sales->fees,
            'shipping_fees' => (int) $sales->fees,
            'collected_online' => $online - $refunds,
            'refunds' => $refunds,
            'collected_on_delivery' => $onDelivery,
            'collected' => $online - $refunds + $onDelivery,
            'remitted' => (int) CourierRemittance::query()->valid()->whereBetween('received_at', [$period->from, $period->to])->sum('amount'),
            'cash_with_couriers' => (int) CashSettlement::pending(Order::query())->selectRaw(CashSettlement::owedSql().' as owed')->value('owed'),
            'delivered_in_period' => $this->deliveredIn($period)->count(),
        ];
    }

    /**
     * Sales of the period per delivery zone, biggest revenue first.
     *
     * @return Collection<int, array{zone: string, orders: int, revenue: int, shipping_fees: int}>
     */
    public function byZone(FinancePeriod $period): Collection
    {
        return $this->sales->sales()->whereBetween('created_at', [$period->from, $period->to])
            ->selectRaw('zone_name, COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue, COALESCE(SUM(shipping_fee), 0) as fees')
            ->groupBy('zone_name')
            ->orderByDesc('revenue')
            ->toBase()
            ->get()
            ->map(fn (object $row) => [
                'zone' => (string) ($row->zone_name ?: 'Sans zone'),
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
                'shipping_fees' => (int) $row->fees,
            ]);
    }

    /**
     * Each courier over the period: deliveries made and failed, fees of their deliveries, cash collected, payments
     * received; and what they still owe today. Couriers with nothing in the period and nothing owed are left out.
     *
     * @return Collection<int, array{courier: Courier, delivered: int, failed: int, success_rate: ?int, shipping_fees: int, collected: int, remitted: int, due: int}>
     */
    public function byCourier(FinancePeriod $period): Collection
    {
        $delivered = $this->deliveredIn($period)->whereNotNull('courier_id')
            ->selectRaw('courier_id, COUNT(*) as orders, COALESCE(SUM(shipping_fee), 0) as fees, COALESCE(SUM(cash_collected), 0) as collected')
            ->groupBy('courier_id')
            ->toBase()
            ->get()
            ->keyBy('courier_id');
        $failed = $this->movedIn($period, OrderStatus::Cancelled)->whereNotNull('courier_id')
            ->selectRaw('courier_id, COUNT(*) as orders')
            ->groupBy('courier_id')
            ->toBase()
            ->pluck('orders', 'courier_id');
        $remitted = CourierRemittance::query()->valid()->whereBetween('received_at', [$period->from, $period->to])
            ->selectRaw('courier_id, SUM(amount) as amount')
            ->groupBy('courier_id')
            ->toBase()
            ->pluck('amount', 'courier_id');
        $due = CashSettlement::pending(Order::query())
            ->selectRaw('courier_id, '.CashSettlement::owedSql().' as owed')
            ->groupBy('courier_id')
            ->toBase()
            ->pluck('owed', 'courier_id');

        return Courier::query()->with('user')->get()
            ->map(function (Courier $courier) use ($delivered, $failed, $remitted, $due): array {
                $id = $courier->getKey();
                $done = (int) ($delivered[$id]->orders ?? 0);
                $ko = (int) ($failed[$id] ?? 0);

                return [
                    'courier' => $courier,
                    'delivered' => $done,
                    'failed' => $ko,
                    'success_rate' => $done + $ko > 0 ? (int) round($done / ($done + $ko) * 100) : null,
                    'shipping_fees' => (int) ($delivered[$id]->fees ?? 0),
                    'collected' => (int) ($delivered[$id]->collected ?? 0),
                    'remitted' => (int) ($remitted[$id] ?? 0),
                    'due' => (int) ($due[$id] ?? 0),
                ];
            })
            ->filter(fn (array $row) => $row['delivered'] + $row['failed'] + $row['remitted'] + $row['due'] > 0)
            ->sortByDesc('delivered')
            ->values();
    }

    /**
     * Revenue and sales per day, week or month of the period, oldest first, empty buckets at 0.
     *
     * @return Collection<int, array{label: string, revenue: int, orders: int}>
     */
    public function series(FinancePeriod $period): Collection
    {
        $bucket = $period->bucket();
        $key = fn (CarbonImmutable $date): string => match ($bucket) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->startOfWeek()->format('Y-m-d'),
            default => $date->format('Y-m'),
        };

        $totals = $this->sales->sales()->whereBetween('created_at', [$period->from, $period->to])
            ->toBase()
            ->get(['created_at', 'total'])
            ->groupBy(fn (object $order) => $key(CarbonImmutable::parse($order->created_at)))
            ->map(fn (Collection $orders) => ['revenue' => (int) $orders->sum('total'), 'orders' => $orders->count()]);

        $buckets = collect();
        $cursor = match ($bucket) {
            'day' => $period->from->startOfDay(),
            'week' => $period->from->startOfWeek(),
            default => $period->from->startOfMonth(),
        };

        while ($cursor->lessThanOrEqualTo($period->to)) {
            $buckets->push([
                'label' => match ($bucket) {
                    'day' => $cursor->format('d/m'),
                    'week' => 'Sem. du '.$cursor->format('d/m'),
                    default => ucfirst($cursor->translatedFormat('M Y')),
                },
                ...($totals[$key($cursor)] ?? ['revenue' => 0, 'orders' => 0]),
            ]);
            $cursor = match ($bucket) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonth(),
            };
        }

        return $buckets;
    }

    /**
     * Orders delivered within the period (date of the "Livrée" step).
     *
     * @return Builder<Order>
     */
    private function deliveredIn(FinancePeriod $period): Builder
    {
        return $this->movedIn($period, OrderStatus::Delivered)->where('status', OrderStatus::Delivered);
    }

    /**
     * @return Builder<Order>
     */
    private function movedIn(FinancePeriod $period, OrderStatus $to): Builder
    {
        return Order::query()
            ->where('status', $to)
            ->whereHas('statusHistory', fn (Builder $history) => $history
                ->where('to_status', $to)
                ->whereBetween('created_at', [$period->from, $period->to]));
    }
}
