<?php

namespace App\Http\Controllers\Courier;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Delivery\DispatchException;
use App\Services\Orders\OrderStatusException;
use App\Services\Orders\OrderStatusManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Courier area (F-124, F-125): my deliveries, the queue of my zones, and one delivery with the customer's
 * address, call, WhatsApp and route, then "en route", "livrée" (cash collected) or "échec" (with a reason).
 * Any order outside the courier's zones and deliveries answers 404.
 */
class DeliveryController extends Controller
{
    public function __construct(
        private DeliveryDispatcher $dispatcher,
        private OrderStatusManager $statuses,
    ) {}

    public function index(Request $request): View
    {
        $courier = $this->courier($request);

        return view('courier.index', [
            'courier' => $courier,
            // On the way first, then ready to leave, then still being prepared.
            'mine' => $courier->openOrders()
                ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [OrderStatus::OutForDelivery->value, OrderStatus::Shipped->value])
                ->orderBy('assigned_at')
                ->get(),
            'queue' => $this->dispatcher->queueFor($courier)->with('items')->oldest('id')->get(),
            'deliveredToday' => $courier->orders()->where('status', OrderStatus::Delivered)->whereDate('updated_at', today())->count(),
            'cashToHandOver' => (int) $courier->orders()->whereNotNull('cash_collected')->whereNull('cash_settled_at')->sum('cash_collected'),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $courier = $this->courier($request);
        $this->ensureVisible($courier, $order);

        return view('courier.show', [
            'order' => $order->load('items'),
            'isMine' => $order->courier_id === $courier->getKey(),
            'steps' => $this->statuses->availableSteps($order, $request->user()),
        ]);
    }

    public function accept(Request $request, Order $order): RedirectResponse
    {
        $courier = $this->courier($request);

        // Taken meanwhile by someone else: say so rather than "not found".
        if ($order->courier_id === null) {
            $this->ensureVisible($courier, $order);
        }

        try {
            $this->dispatcher->accept($order, $courier);
        } catch (DispatchException $exception) {
            return redirect()->route('courier.home')->with('courier_error', $exception->getMessage());
        }

        return redirect()->route('courier.orders.show', $order)->with('courier_status', "La livraison {$order->number} est à vous.");
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        return $this->move($request, $order, OrderStatus::OutForDelivery, null, 'Bonne route ! Le client est prévenu par SMS.');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $toCollect = $order->amountToCollect();
        $data = $request->validate([
            'cash_collected' => [$toCollect > 0 ? 'required' : 'nullable', 'integer', 'min:0', 'max:10000000'],
        ], [], ['cash_collected' => 'montant encaissé']);

        return $this->move($request, $order, OrderStatus::Delivered, null, 'Livraison enregistrée. Merci !', $toCollect > 0 ? (int) $data['cash_collected'] : null);
    }

    public function fail(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']], [], ['reason' => 'motif']);

        return $this->move($request, $order, OrderStatus::Cancelled, 'Échec de livraison : '.$data['reason'], 'Échec enregistré. La boutique est prévenue.');
    }

    private function move(Request $request, Order $order, OrderStatus $to, ?string $note, string $done, ?int $cash = null): RedirectResponse
    {
        $this->ensureVisible($this->courier($request), $order);

        try {
            DB::transaction(function () use ($request, $order, $to, $note, $cash): void {
                if ($cash !== null) {
                    $order->forceFill(['cash_collected' => $cash])->save();
                }

                $this->statuses->move($order, $to, $request->user(), $note);
            });
        } catch (OrderStatusException $exception) {
            return back()->with('courier_error', $exception->getMessage());
        }

        return $to === OrderStatus::OutForDelivery
            ? redirect()->route('courier.orders.show', $order)->with('courier_status', $done)
            : redirect()->route('courier.home')->with('courier_status', $done);
    }

    private function courier(Request $request): Courier
    {
        return $request->user()->courier;
    }

    private function ensureVisible(Courier $courier, Order $order): void
    {
        abort_unless($this->dispatcher->visibleTo($courier)->whereKey($order->getKey())->exists(), 404);
    }
}
