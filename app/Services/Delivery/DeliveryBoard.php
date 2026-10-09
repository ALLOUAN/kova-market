<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The delivery dashboard (back-office › Livraison): what is happening today, what is stuck, who delivers what and
 * which zones are covered. Times are those of the last status change (status history).
 */
class DeliveryBoard
{
    /** A confirmed order still without courier after this long is late to dispatch. */
    public const WAITING_ALERT_HOURS = 2;

    /** A delivery on the way for longer than this needs a call to the courier. */
    public const ON_THE_WAY_ALERT_HOURS = 3;

    public function __construct(
        private DeliveryDispatcher $dispatcher,
        private CashSettlement $settlement,
    ) {}

    /**
     * @return array<string, int|null>
     */
    public function stats(): array
    {
        $delivered7 = $this->movedSince(OrderStatus::Delivered, today()->subDays(6))->count();
        $failed7 = $this->failedSince(today()->subDays(6))->count();

        return [
            'to_assign' => $this->dispatcher->unassigned()->count(),
            'to_prepare' => Order::query()->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Preparing])->count(),
            'on_the_way' => Order::query()->where('status', OrderStatus::OutForDelivery)->count(),
            'interior_in_transit' => Order::query()->where('status', OrderStatus::Shipped)->where('delivery_mode', DeliveryMode::Interior)->count(),
            'delivered_today' => $this->movedSince(OrderStatus::Delivered, today())->count(),
            'failed_today' => $this->failedSince(today())->count(),
            'success_rate_7d' => $delivered7 + $failed7 > 0 ? (int) round($delivered7 / ($delivered7 + $failed7) * 100) : null,
            'couriers_available' => Courier::query()->available()->count(),
            'couriers_on_the_way' => Order::query()->where('status', OrderStatus::OutForDelivery)->whereNotNull('courier_id')->distinct()->count('courier_id'),
            'cash_with_couriers' => (int) CashSettlement::pending(Order::query())->selectRaw(CashSettlement::owedSql().' as owed')->value('owed'),
        ];
    }

    /**
     * What needs someone now: orders waiting too long for a courier, deliveries on the way for too long, open
     * zones nobody can deliver.
     *
     * @return array{waiting: Collection<int, Order>, on_the_way: Collection<int, Order>, uncovered_zones: Collection<int, DeliveryZone>}
     */
    public function alerts(): array
    {
        return [
            'waiting' => $this->dispatcher->unassigned()
                ->addSelect(['orders.*', 'moved_at' => self::lastMoveQuery()])
                ->where(self::lastMoveQuery(), '<=', now()->subHours(self::WAITING_ALERT_HOURS))
                ->orderBy('moved_at')
                ->limit(10)
                ->get(),
            'on_the_way' => Order::query()->where('status', OrderStatus::OutForDelivery)->with('courier.user')
                ->addSelect(['orders.*', 'moved_at' => self::lastMoveQuery()])
                ->where(self::lastMoveQuery(), '<=', now()->subHours(self::ON_THE_WAY_ALERT_HOURS))
                ->orderBy('moved_at')
                ->limit(10)
                ->get(),
            'uncovered_zones' => DeliveryZone::query()
                ->where('is_active', true)->whereNotNull('fee')->where('delivery_mode', DeliveryMode::Abidjan)
                ->whereDoesntHave('couriers', fn (Builder $query) => $query->available())
                ->orderBy('position')
                ->get(),
        ];
    }

    /**
     * Every courier and their day: deliveries in progress and on the way, delivered and failed today, cash owed.
     * Available couriers first, the busiest on top.
     *
     * @return Collection<int, array{courier: Courier, open: int, on_the_way: int, delivered_today: int, failed_today: int, due: int}>
     */
    public function couriers(): Collection
    {
        $deliveredToday = $this->movedSince(OrderStatus::Delivered, today())->whereNotNull('courier_id')
            ->selectRaw('courier_id, COUNT(*) as n')->groupBy('courier_id')->toBase()->pluck('n', 'courier_id');
        $failedToday = $this->failedSince(today())->selectRaw('courier_id, COUNT(*) as n')->groupBy('courier_id')->toBase()->pluck('n', 'courier_id');
        $due = CashSettlement::pending(Order::query())->selectRaw('courier_id, '.CashSettlement::owedSql().' as owed')
            ->groupBy('courier_id')->toBase()->pluck('owed', 'courier_id');

        return Courier::query()
            ->with(['user', 'zones'])
            ->withCount(['openOrders', 'orders as on_the_way_count' => fn (Builder $query) => $query->where('status', OrderStatus::OutForDelivery)])
            ->get()
            ->map(fn (Courier $courier) => [
                'courier' => $courier,
                'open' => (int) $courier->open_orders_count,
                'on_the_way' => (int) $courier->on_the_way_count,
                'delivered_today' => (int) ($deliveredToday[$courier->id] ?? 0),
                'failed_today' => (int) ($failedToday[$courier->id] ?? 0),
                'due' => (int) ($due[$courier->id] ?? 0),
            ])
            ->sortBy([
                fn (array $a, array $b) => $a['courier']->isSuspended() <=> $b['courier']->isSuspended(),
                fn (array $a, array $b) => $b['open'] <=> $a['open'],
            ])
            ->values();
    }

    /**
     * Every zone: waiting for a courier, in progress, delivered today, couriers able to deliver it.
     *
     * @return Collection<int, array{zone: DeliveryZone, waiting: int, in_progress: int, delivered_today: int, couriers: int}>
     */
    public function zones(): Collection
    {
        $byZone = fn (Builder $orders) => $orders->join('communes', 'communes.id', '=', 'orders.commune_id')
            ->selectRaw('communes.delivery_zone_id as zone_id, COUNT(*) as n')
            ->groupBy('communes.delivery_zone_id')
            ->toBase()
            ->pluck('n', 'zone_id');

        $waiting = $byZone($this->dispatcher->unassigned());
        $inProgress = $byZone(Order::query()->whereIn('orders.status', Courier::OPEN_STATUSES)->where(fn (Builder $query) => $query
            ->whereNotNull('orders.courier_id')->orWhere('orders.delivery_mode', DeliveryMode::Interior)));
        $deliveredToday = $byZone($this->movedSince(OrderStatus::Delivered, today()));

        return DeliveryZone::query()
            ->withCount(['couriers' => fn (Builder $query) => $query->available()])
            ->orderBy('position')
            ->get()
            ->map(fn (DeliveryZone $zone) => [
                'zone' => $zone,
                'waiting' => (int) ($waiting[$zone->id] ?? 0),
                'in_progress' => (int) ($inProgress[$zone->id] ?? 0),
                'delivered_today' => (int) ($deliveredToday[$zone->id] ?? 0),
                'couriers' => (int) $zone->couriers_count,
            ]);
    }

    /**
     * When the order reached its current status (last row of its history).
     */
    public static function lastMoveQuery(): QueryBuilder
    {
        return DB::table('order_status_histories')
            ->selectRaw('MAX(created_at)')
            ->whereColumn('order_status_histories.order_id', 'orders.id');
    }

    /**
     * Orders now in this status, reached since the given time.
     *
     * @return Builder<Order>
     */
    private function movedSince(OrderStatus $status, DateTimeInterface $since): Builder
    {
        return Order::query()->where('orders.status', $status)->whereHas('statusHistory', fn (Builder $history) => $history
            ->where('to_status', $status)->where('created_at', '>=', $since));
    }

    /**
     * Deliveries that failed since the given time: cancelled while a courier had them.
     *
     * @return Builder<Order>
     */
    private function failedSince(DateTimeInterface $since): Builder
    {
        return $this->movedSince(OrderStatus::Cancelled, $since)->whereNotNull('orders.courier_id');
    }
}
