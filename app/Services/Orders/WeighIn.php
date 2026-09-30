<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderUpdateForCustomer;
use App\Notifications\WeighInForCustomer;
use App\Services\Catalog\InsufficientStock;
use App\Services\Catalog\StockManager;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Weigh-in at preparation (Mon Marché): the picker records the quantity really weighed of the lines sold by weight
 * or volume, and the order is billed on it — line, total and cash to collect, with the stock difference. Only for
 * orders paid on delivery and not paid yet, before they leave: an order paid online keeps the amount paid. The
 * weighed quantity stays within a tolerance of what the customer ordered (Paramètres › Commandes, 10 % by default).
 */
class WeighIn
{
    public const DEFAULT_TOLERANCE = 10;

    /** Steps during which the order can still be weighed. */
    private const OPEN_STATUSES = [OrderStatus::Received, OrderStatus::Confirmed, OrderStatus::Preparing];

    public function __construct(private StockManager $stock) {}

    public static function tolerance(): int
    {
        return max(0, min(50, (int) Setting::get('orders.weigh_tolerance', self::DEFAULT_TOLERANCE)));
    }

    /** Whether the order has lines to weigh and can still be billed on their weight. */
    public function applies(Order $order): bool
    {
        return $this->reasonNotAllowed($order) === null && $this->weighableItems($order)->isNotEmpty();
    }

    /** Why the order cannot be weighed, or null. */
    public function reasonNotAllowed(Order $order): ?string
    {
        return match (true) {
            self::tolerance() === 0 => 'La pesée est désactivée (Paramètres › Commandes).',
            $order->payment_method !== PaymentMethod::CashOnDelivery || $order->payment_status !== PaymentStatus::Pending => 'Commande payée en ligne : le montant payé reste celui de la commande.',
            ! in_array($order->status, self::OPEN_STATUSES, true) => 'La commande est déjà partie : la pesée n’est plus possible.',
            default => null,
        };
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function weighableItems(Order $order): Collection
    {
        return $order->items->filter(fn (OrderItem $item) => $item->saleQuantity()->unit->isMeasured())->values();
    }

    /** Lowest and highest quantity accepted for a line, in base units. */
    public function bounds(OrderItem $item): array
    {
        $ordered = $item->ordered_quantity ?? $item->quantity;
        $margin = (int) round($ordered * self::tolerance() / 100);

        return [max(1, $ordered - $margin), $ordered + $margin];
    }

    /**
     * Sets the weighed quantities (item id => base units) and bills the order on them.
     *
     * @param  array<int, int>  $weighed
     *
     * @throws WeighInException
     */
    public function record(Order $order, array $weighed, User $user): Order
    {
        if ($reason = $this->reasonNotAllowed($order)) {
            throw new WeighInException($reason);
        }

        $before = $order->fresh()->total;

        $order = DB::transaction(function () use ($order, $weighed, $user): Order {
            $order = Order::lockForUpdate()->with('items.variant')->findOrFail($order->getKey());
            $changes = [];

            foreach ($this->weighableItems($order) as $item) {
                if (! isset($weighed[$item->id])) {
                    continue;
                }

                $quantity = (int) $weighed[$item->id];
                [$min, $max] = $this->bounds($item);
                $rules = $item->saleQuantity();

                if ($quantity < $min || $quantity > $max) {
                    throw new WeighInException("« {$item->product_name} » : entre {$rules->format($min)} et {$rules->format($max)} (écart de ".self::tolerance().' % au plus avec la commande).');
                }

                $difference = $quantity - $item->quantity;

                // Weighed heavier: the extra comes out of the stock; lighter: the rest goes back on sale.
                if ($difference !== 0 && $item->variant) {
                    try {
                        $this->stock->adjust(
                            $item->variant,
                            -$difference,
                            $difference > 0 ? StockMovementReason::Sale : StockMovementReason::Release,
                            $user,
                            "Pesée {$order->number}",
                        );
                    } catch (InsufficientStock) {
                        throw new WeighInException("« {$item->product_name} » : le stock ne permet pas {$rules->format($quantity)}.");
                    }
                }

                $changes[] = "{$item->product_name} : {$rules->format($item->ordered_quantity ?? $item->quantity)} commandés, {$rules->format($quantity)} pesés";

                $item->forceFill([
                    'ordered_quantity' => $item->ordered_quantity ?? $item->quantity,
                    'quantity' => $quantity,
                    'line_total' => $rules->lineTotal($item->unit_price, $quantity),
                    'weighed_at' => now(),
                ])->save();
            }

            // The discount never exceeds the goods; the delivery fee stays the one of the order.
            $subtotal = (int) $order->items()->sum('line_total');
            $discount = min($order->discount, $subtotal);
            $before = $order->total;

            $order->forceFill([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $subtotal - $discount + $order->shipping_fee,
            ])->save();

            activity()->causedBy($user)->performedOn($order)
                ->withProperties(['lines' => $changes, 'total_before' => $before, 'total_after' => $order->total])
                ->log('Pesée : total '.Money::format($before).' → '.Money::format($order->total));

            return $order->fresh('items');
        });

        // The customer knows the amount to pay before the courier arrives.
        if ($order->total !== $before) {
            Notification::send(OrderUpdateForCustomer::recipientOf($order), new WeighInForCustomer($order, $before));
        }

        return $order;
    }
}
