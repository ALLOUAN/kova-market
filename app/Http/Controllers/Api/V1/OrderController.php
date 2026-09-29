<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\TrackedOrderResource;
use App\Models\Order;
use App\Services\Cart\CartManager;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\PlaceOrder;
use App\Services\Payments\CinetPayException;
use App\Services\Payments\OnlinePayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placing an order from the cart (F-050 to F-056) and tracking it (F-073) through the API. Orders placed here
 * are marked "mobile".
 */
class OrderController extends Controller
{
    public const SOURCE = 'mobile';

    public function store(PlaceOrderRequest $request, CartManager $cart, PlaceOrder $placeOrder, OnlinePayments $payments): JsonResponse
    {
        $order = $placeOrder->handle(
            $cart->current() ?? throw new CheckoutException('Votre panier est vide.'),
            $request->details(),
            $request->user(),
            self::SOURCE,
        );

        if ($request->boolean('save_address')) {
            $request->user()?->saveAddressFromOrder($order);
        }

        return OrderResource::make($order->load(['items', 'statusHistory', 'courier.user']))
            ->additional($order->payment_method->isOnline() ? ['payment' => $this->openPayment($order, $payments)] : [])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * A new payment attempt for an online order still unpaid (F-060): the app opens payment.url in a browser.
     * The order's phone is asked, as for tracking, so that only its customer can do it.
     */
    public function pay(Request $request, Order $order, OnlinePayments $payments): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:30']], [], ['phone' => 'téléphone']);

        abort_unless(Order::findForTracking($order->number, $data['phone'])?->is($order), 404, 'Commande introuvable.');
        abort_unless($order->awaitsOnlinePayment(), 422, 'Cette commande n’attend pas de paiement en ligne.');

        return response()->json(['payment' => $this->openPayment($order, $payments)]);
    }

    /**
     * @return array{url: ?string, reference: ?string, error: ?string}
     */
    private function openPayment(Order $order, OnlinePayments $payments): array
    {
        try {
            $payment = $payments->start($order);

            return ['url' => $payment->payment_url, 'reference' => $payment->merchant_transaction_id, 'error' => null];
        } catch (CinetPayException $exception) {
            return ['url' => null, 'reference' => null, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Same answer whatever is wrong: the API never reveals whether an order number exists.
     */
    public function track(Request $request): TrackedOrderResource
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
        ], [], ['number' => 'numéro de commande', 'phone' => 'téléphone']);

        $order = Order::findForTracking($data['number'], $data['phone']);

        abort_if($order === null, 404, 'Commande introuvable. Vérifiez le numéro et le téléphone indiqués lors de la commande.');

        return TrackedOrderResource::make($order);
    }
}
