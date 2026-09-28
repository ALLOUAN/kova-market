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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placing an order from the cart (F-050 to F-056) and tracking it (F-073) through the API. Orders placed here
 * are marked "mobile".
 */
class OrderController extends Controller
{
    public const SOURCE = 'mobile';

    public function store(PlaceOrderRequest $request, CartManager $cart, PlaceOrder $placeOrder): JsonResponse
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

        return OrderResource::make($order->load(['items', 'statusHistory', 'courier.user']))->response()->setStatusCode(201);
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
