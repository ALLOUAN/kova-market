<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Sales figures of the dashboard. A sale is an order that is not cancelled and not an online order still waiting
 * for its payment (it may never be paid).
 */
class SalesFigures
{
    /** Steps where the team still has something to do. */
    public const TO_HANDLE = [OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing];

    /** @return Builder<Order> */
    public function sales(): Builder
    {
        return Order::query()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->where(fn (Builder $query) => $query
                ->where('payment_method', PaymentMethod::CashOnDelivery)
                ->orWhere('payment_status', PaymentStatus::Paid));
    }

    public function revenue(Carbon $from, Carbon $to): int
    {
        return (int) $this->sales()->whereBetween('created_at', [$from, $to])->sum('total');
    }

    public function count(Carbon $from, Carbon $to): int
    {
        return $this->sales()->whereBetween('created_at', [$from, $to])->count();
    }

    public function toHandle(): int
    {
        return Order::query()->whereIn('status', self::TO_HANDLE)
            ->where(fn (Builder $query) => $query
                ->where('payment_method', PaymentMethod::CashOnDelivery)
                ->orWhere('payment_status', PaymentStatus::Paid))
            ->count();
    }

    /**
     * Revenue and number of sales per day over the last days, oldest first, days without sales at 0.
     *
     * @return Collection<string, array{revenue: int, orders: int}> keyed by Y-m-d
     */
    public function daily(int $days): Collection
    {
        $from = today()->subDays($days - 1);

        $rows = $this->sales()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return collect(range(0, $days - 1))
            ->mapWithKeys(function (int $offset) use ($from, $rows): array {
                $day = $from->copy()->addDays($offset)->toDateString();

                return [$day => ['revenue' => (int) ($rows[$day]->revenue ?? 0), 'orders' => (int) ($rows[$day]->orders ?? 0)]];
            });
    }
}
