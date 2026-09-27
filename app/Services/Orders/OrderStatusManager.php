<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\StockMovementReason;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\StockManager;
use Illuminate\Support\Facades\DB;

/**
 * Moves orders through their life cycle (F-121). Every change is checked (allowed step, permission), recorded
 * with its author and reason, and has its effects: a cancellation puts the stock and the promo code use back,
 * a delivery marks a cash-on-delivery order as paid and counts the sales. Only a super-admin can go back one
 * step, with a reason.
 */
class OrderStatusManager
{
    /** Steps an order picker may take (préparation, expédition). */
    private const PICKER_STEPS = [
        [OrderStatus::Confirmed, OrderStatus::Preparing],
        [OrderStatus::Preparing, OrderStatus::Shipped],
    ];

    /** Steps a courier may take on their own deliveries: on the way, delivered, failed (F-125). */
    private const COURIER_STEPS = [
        [OrderStatus::Shipped, OrderStatus::OutForDelivery],
        [OrderStatus::OutForDelivery, OrderStatus::Delivered],
        [OrderStatus::OutForDelivery, OrderStatus::Cancelled],
    ];

    public function __construct(private StockManager $stock) {}

    /**
     * Next statuses this user may give the order.
     *
     * @return list<OrderStatus>
     */
    public function availableSteps(Order $order, User $user): array
    {
        return array_values(array_filter(
            $order->status->next(),
            fn (OrderStatus $to) => $this->mayMove($user, $order, $to),
        ));
    }

    /**
     * @throws OrderStatusException
     */
    public function move(Order $order, OrderStatus $to, User $user, ?string $note = null): Order
    {
        if (! $order->status->canBecome($to)) {
            throw new OrderStatusException("Une commande « {$order->status->getLabel()} » ne peut pas passer à « {$to->getLabel()} ».");
        }

        if (! $this->mayMove($user, $order, $to)) {
            throw new OrderStatusException('Vous n’avez pas le droit d’effectuer ce changement de statut.');
        }

        if ($to === OrderStatus::Cancelled && blank($note)) {
            throw new OrderStatusException('Indiquez le motif de l’annulation.');
        }

        return $this->apply($order, $to, $user, $note, function (Order $order) use ($to, $user): void {
            match ($to) {
                OrderStatus::Cancelled => $this->cancel($order, $user),
                OrderStatus::Delivered => $this->deliver($order),
                default => null,
            };
        });
    }

    /**
     * Super-admin correction, one step back in the normal flow. A cancellation cannot be undone this way:
     * its stock is already back on sale.
     */
    public function mayRollBack(Order $order, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin->value) && $order->status->previous() !== null;
    }

    /**
     * @throws OrderStatusException
     */
    public function rollBack(Order $order, User $user, ?string $note): Order
    {
        if (! $this->mayRollBack($order, $user)) {
            throw new OrderStatusException('Ce retour en arrière n’est pas possible.');
        }

        if (blank($note)) {
            throw new OrderStatusException('Indiquez le motif du retour en arrière.');
        }

        $wasDelivered = $order->status === OrderStatus::Delivered;

        return $this->apply($order, $order->status->previous(), $user, "Retour en arrière : {$note}", function (Order $order) use ($wasDelivered): void {
            if ($wasDelivered) {
                $this->undeliver($order);
            }
        });
    }

    private function mayMove(User $user, Order $order, OrderStatus $to): bool
    {
        $from = $order->status;

        if ($user->can(Permission::ManageOrders->value)) {
            return true;
        }

        // Couriers only move the deliveries given to them (F-124).
        if ($user->hasRole(Role::Courier->value)) {
            return $order->courier_id !== null
                && $order->courier_id === $user->courier?->getKey()
                && in_array([$from, $to], self::COURIER_STEPS, true);
        }

        return $user->can(Permission::PrepareOrders->value) && in_array([$from, $to], self::PICKER_STEPS, true);
    }

    private function apply(Order $order, OrderStatus $to, User $user, ?string $note, callable $effects): Order
    {
        $from = $order->status;

        DB::transaction(function () use ($order, $from, $to, $user, $note, $effects): void {
            $order->status = $to;
            $effects($order);
            $order->save();

            $order->statusHistory()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $user->getKey(),
                'note' => $note,
            ]);
        });

        OrderStatusChanged::dispatch($order, $from, $to);

        return $order;
    }

    private function cancel(Order $order, User $user): void
    {
        $order->items()->with('variant')->get()
            ->filter(fn (OrderItem $item) => $item->variant !== null)
            ->each(fn (OrderItem $item) => $this->stock->adjust(
                $item->variant, $item->quantity, StockMovementReason::Release, $user, "Annulation {$order->number}",
            ));

        // The promo code use is given back: it counts again for the global and per-customer caps.
        if ($usage = $order->couponUsage) {
            $usage->coupon()->where('times_used', '>', 0)->decrement('times_used');
            $usage->delete();
        }

        $order->payment_status = PaymentStatus::Cancelled;
    }

    private function deliver(Order $order): void
    {
        if ($order->payment_method === PaymentMethod::CashOnDelivery) {
            $order->payment_status = PaymentStatus::Paid;
        }

        $this->countSales($order, 1);
    }

    private function undeliver(Order $order): void
    {
        if ($order->payment_method === PaymentMethod::CashOnDelivery) {
            $order->payment_status = PaymentStatus::Pending;
        }

        $this->countSales($order, -1);
    }

    /**
     * Delivered orders feed the "popular" sort and the best-seller figures (C-11).
     */
    private function countSales(Order $order, int $direction): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::whereKey($item->product_id)->increment('sold_count', $direction * $item->quantity);
            }
        }
    }
}
