<?php

namespace App\Services\Delivery;

use App\Enums\OrderStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Cash collected on delivery and handed over to the store (F-126). What a courier owes is the cash of their
 * delivered orders not settled yet; settling marks them all at once, with who received the money.
 */
class CashSettlement
{
    /**
     * @return HasMany<Order>
     */
    public static function pendingOrders(Courier $courier): HasMany
    {
        return $courier->orders()->tap(fn (Builder|HasMany $query) => self::pending($query));
    }

    /**
     * Delivered orders whose cash is still with the courier.
     */
    public static function pending(Builder|HasMany $query): Builder|HasMany
    {
        return $query->where('status', OrderStatus::Delivered)->whereNotNull('cash_collected')->whereNull('cash_settled_at');
    }

    public function due(Courier $courier): int
    {
        return (int) self::pendingOrders($courier)->sum('cash_collected');
    }

    /**
     * Records that the courier handed over all the cash due; returns the amount.
     */
    public function settle(Courier $courier, User $by): int
    {
        return DB::transaction(function () use ($courier, $by): int {
            $orders = self::pendingOrders($courier)->lockForUpdate()->get();
            $amount = (int) $orders->sum('cash_collected');

            if ($orders->isEmpty()) {
                return 0;
            }

            Order::whereKey($orders->modelKeys())->update(['cash_settled_at' => now(), 'cash_settled_by' => $by->getKey()]);

            activity('livraisons')->performedOn($courier)->causedBy($by)
                ->withProperties(['montant' => $amount, 'commandes' => $orders->pluck('number')->all()])
                ->log("Encaissements reçus de {$courier->name()}");

            return $amount;
        });
    }
}
