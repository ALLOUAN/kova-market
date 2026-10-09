<?php

namespace App\Services\Delivery;

use App\Enums\OrderStatus;
use App\Enums\RemittanceMethod;
use App\Models\Courier;
use App\Models\CourierRemittance;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Cash collected on delivery and handed over to the store (F-126). A courier owes the cash of their delivered
 * orders minus what they already handed over. Each payment, whole or partial, covers the oldest orders first;
 * an order is settled once all its cash is in. A payment is never deleted: cancelling it makes its orders owe
 * their cash again.
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
     * Delivered orders whose cash is still, in whole or in part, with the courier.
     */
    public static function pending(Builder|HasMany $query): Builder|HasMany
    {
        return $query->where('status', OrderStatus::Delivered)->whereNotNull('cash_collected')->whereNull('cash_settled_at');
    }

    /**
     * SQL of the cash still owed on one order, to sum over pending orders.
     */
    public static function owedSql(): string
    {
        return 'COALESCE(SUM(cash_collected - cash_remitted), 0)';
    }

    public function due(Courier $courier): int
    {
        return (int) self::pendingOrders($courier)->selectRaw(self::owedSql().' as owed')->value('owed');
    }

    /**
     * Records cash handed over by the courier and spreads it over their oldest orders not settled yet.
     */
    public function record(Courier $courier, int $amount, RemittanceMethod $method, User $by, ?CarbonInterface $receivedAt = null, ?string $reference = null, ?string $note = null): CourierRemittance
    {
        return DB::transaction(function () use ($courier, $amount, $method, $by, $receivedAt, $reference, $note): CourierRemittance {
            // One payment at a time per courier: two people recording at once could not cover the same orders.
            Courier::whereKey($courier->getKey())->lockForUpdate()->first();

            $orders = self::pendingOrders($courier)->orderBy('assigned_at')->orderBy('id')->lockForUpdate()->get();
            $due = (int) $orders->sum(fn (Order $order) => $order->cash_collected - $order->cash_remitted);

            if ($amount < 1) {
                throw new RemittanceException('Le montant du versement doit être supérieur à 0.');
            }

            if ($amount > $due) {
                throw new RemittanceException('Le versement dépasse ce que le livreur doit encore : '.Money::format($due).'.');
            }

            $remittance = $courier->remittances()->create([
                'amount' => $amount,
                'balance_after' => $due - $amount,
                'method' => $method,
                'reference' => $reference,
                'received_at' => $receivedAt ?? now(),
                'received_by' => $by->getKey(),
                'note' => $note,
            ]);

            $left = $amount;

            foreach ($orders as $order) {
                if ($left === 0) {
                    break;
                }

                $part = min($left, $order->cash_collected - $order->cash_remitted);
                $left -= $part;
                $remittance->orders()->attach($order->getKey(), ['amount' => $part]);

                $order->cash_remitted += $part;

                if ($order->cash_remitted >= $order->cash_collected) {
                    $order->cash_settled_at = $remittance->received_at;
                    $order->cash_settled_by = $by->getKey();
                }

                $order->save();
            }

            activity('livraisons')->performedOn($remittance)->causedBy($by)
                ->withProperties(['livreur' => $courier->name(), 'montant' => $amount, 'mode' => $method->getLabel(), 'reste_du' => $due - $amount])
                ->log("Versement de {$courier->name()} : ".Money::format($amount));

            return $remittance;
        });
    }

    /**
     * Records that the courier handed over all the cash due, in cash; returns the amount.
     */
    public function settle(Courier $courier, User $by): int
    {
        $due = $this->due($courier);

        return $due > 0 ? $this->record($courier, $due, RemittanceMethod::Cash, $by)->amount : 0;
    }

    /**
     * Cancels a payment recorded by mistake: its orders owe their part again. The payment stays, marked cancelled.
     */
    public function cancel(CourierRemittance $remittance, string $reason, User $by): void
    {
        DB::transaction(function () use ($remittance, $reason, $by): void {
            $remittance = CourierRemittance::whereKey($remittance->getKey())->lockForUpdate()->firstOrFail();

            if ($remittance->isCancelled()) {
                throw new RemittanceException('Ce versement est déjà annulé.');
            }

            foreach ($remittance->orders()->lockForUpdate()->get() as $order) {
                $order->forceFill([
                    'cash_remitted' => max(0, $order->cash_remitted - $order->pivot->amount),
                    'cash_settled_at' => null,
                    'cash_settled_by' => null,
                ])->save();
            }

            $remittance->forceFill(['cancelled_at' => now(), 'cancelled_by' => $by->getKey(), 'cancel_reason' => $reason])->save();

            activity('livraisons')->performedOn($remittance)->causedBy($by)
                ->withProperties(['livreur' => $remittance->courier->name(), 'montant' => $remittance->amount, 'motif' => $reason])
                ->log("Versement {$remittance->number()} annulé");
        });
    }
}
