<?php

namespace App\Services\Delivery;

use App\Models\Courier;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\DeliveryForCourier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Who delivers what (F-123, F-124). A confirmed order enters the queue of the zone of its commune; depending on
 * the setting it is given at once to the active courier of the zone with the fewest open deliveries, or waits for
 * the first courier of the zone who takes it. The back-office can always give it to someone else. A courier only
 * ever sees the orders of their zones that nobody took, and their own.
 */
class DeliveryDispatcher
{
    public const AUTOMATIC = 'automatique';

    public const FIRST_TO_ACCEPT = 'premier';

    public function mode(): string
    {
        return Setting::get('delivery.assignment_mode') === self::AUTOMATIC ? self::AUTOMATIC : self::FIRST_TO_ACCEPT;
    }

    /**
     * Orders waiting in the queue of the courier's zones.
     *
     * @return Builder<Order>
     */
    public function queueFor(Courier $courier): Builder
    {
        $zones = $courier->zones()->pluck('delivery_zones.id');

        return $this->waiting()->whereHas('commune', fn (Builder $query) => $query->whereIn('delivery_zone_id', $zones));
    }

    /**
     * Everything a courier may open: their own orders and the queue of their zones.
     *
     * @return Builder<Order>
     */
    public function visibleTo(Courier $courier): Builder
    {
        return Order::query()->where(fn (Builder $query) => $query
            ->where('courier_id', $courier->getKey())
            ->orWhereIn('id', $this->queueFor($courier)->select('orders.id')));
    }

    /**
     * A confirmed order (or one given back) is dispatched according to the mode.
     */
    public function enqueue(Order $order): void
    {
        if ($order->courier_id !== null || ! in_array($order->status, Courier::OPEN_STATUSES, true)) {
            return;
        }

        if ($this->mode() === self::AUTOMATIC && $this->assignAutomatically($order)) {
            return;
        }

        Notification::send(
            $this->couriersOfZone($order)->with('user')->get()->map->user,
            new DeliveryForCourier($order, DeliveryForCourier::WAITING),
        );
    }

    /**
     * The courier of the zone with the fewest open deliveries (the oldest account on a tie), or none.
     */
    public function assignAutomatically(Order $order): ?Courier
    {
        $courier = $this->couriersOfZone($order)->withCount('openOrders')->orderBy('open_orders_count')->orderBy('id')->first();

        if ($courier) {
            $this->assign($order, $courier);
        }

        return $courier;
    }

    /**
     * "Je prends cette livraison": the order row is locked, so of two couriers accepting at once only the first
     * one gets it.
     *
     * @throws DispatchException
     */
    public function accept(Order $order, Courier $courier): Order
    {
        return DB::transaction(function () use ($order, $courier): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->courier_id !== null) {
                throw new DispatchException($locked->courier_id === $courier->getKey()
                    ? 'Cette livraison est déjà la vôtre.'
                    : 'Un autre livreur vient de prendre cette livraison.');
            }

            if (! $this->queueFor($courier)->whereKey($locked->getKey())->exists()) {
                throw new DispatchException('Cette commande n’est plus à livrer dans vos zones.');
            }

            $this->give($locked, $courier, $courier->user);

            return $locked;
        });
    }

    /**
     * Gives the order to a courier (automatic dispatch or back-office), who is told by SMS.
     *
     * @throws DispatchException
     */
    public function assign(Order $order, Courier $courier, ?User $by = null): Order
    {
        if ($courier->isSuspended()) {
            throw new DispatchException('Ce livreur est suspendu.');
        }

        if (! in_array($order->status, Courier::OPEN_STATUSES, true)) {
            throw new DispatchException('Cette commande n’est plus à livrer.');
        }

        $this->give($order, $courier, $by);
        Notification::route('sms', $courier->user->phone)->notify(new DeliveryForCourier($order, DeliveryForCourier::ASSIGNED));

        return $order;
    }

    /**
     * Takes the order back from its courier (suspension, back-office) and puts it in the queue again.
     */
    public function release(Order $order, ?User $by = null): void
    {
        $courier = $order->courier;
        $order->forceFill(['courier_id' => null, 'assigned_at' => null])->save();

        activity('livraisons')->performedOn($order)->causedBy($by)
            ->withProperties(['livreur' => $courier?->name()])
            ->log("Commande {$order->number} retirée au livreur");

        $this->enqueue($order);
    }

    private function give(Order $order, Courier $courier, ?User $by): void
    {
        $order->forceFill(['courier_id' => $courier->getKey(), 'assigned_at' => now()])->save();

        activity('livraisons')->performedOn($order)->causedBy($by)
            ->withProperties(['livreur' => $courier->name()])
            ->log("Commande {$order->number} confiée à {$courier->name()}");
    }

    /**
     * @return Builder<Order>
     */
    private function waiting(): Builder
    {
        return Order::query()->whereNull('courier_id')->whereIn('status', Courier::OPEN_STATUSES);
    }

    /**
     * @return Builder<Courier>
     */
    private function couriersOfZone(Order $order): Builder
    {
        $zone = $order->commune?->delivery_zone_id;

        return Courier::query()->available()->whereHas('zones', fn (Builder $query) => $query->whereKey($zone ?? 0));
    }
}
