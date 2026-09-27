<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Commune;
use App\Models\Order;
use App\Services\Cart\CartManager;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\PlaceOrder;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One-page checkout (F-050) and order confirmation.
 */
class CheckoutController extends Controller
{
    /** Session key listing the orders this visitor placed, so a guest can see its confirmation page. */
    public const PLACED = 'placed_orders';

    public function __construct(private CartManager $cart) {}

    public function show(Request $request): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if ($summary->count() === 0) {
            return redirect()->route('cart.show')->with('cart_error', 'Votre panier est vide ou ses articles ne sont plus disponibles.');
        }

        $user = $request->user();

        return view('pages.checkout', [
            'summary' => $summary,
            'communes' => Commune::deliverable()->with('zone')->get()->groupBy(fn (Commune $commune) => $commune->zone->name),
            'paymentMethods' => PaymentMethod::cases(),
            'defaults' => [
                'customer_name' => $user?->name,
                'phone' => $user?->phone ? PhoneNumber::format($user->phone) : null,
                'email' => $user?->email,
                'commune_id' => $summary->commune?->id,
            ],
        ]);
    }

    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $cart = $this->cart->current();

        if (! $cart) {
            return redirect()->route('cart.show')->with('cart_error', 'Votre panier est vide.');
        }

        try {
            $order = $placeOrder->handle($cart, $request->details(), $request->user());
        } catch (CheckoutException $exception) {
            return redirect()->route('cart.show')->with('cart_error', $exception->getMessage());
        }

        $request->session()->push(self::PLACED, $order->number);

        return redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Request $request, Order $order): View
    {
        // Only the visitor who placed the order (or its account) can see it here.
        $allowed = in_array($order->number, $request->session()->get(self::PLACED, []), true)
            || ($order->user_id !== null && $order->user_id === $request->user()?->getKey());

        abort_unless($allowed, 404);

        return view('pages.order-confirmation', ['order' => $order->load('items')]);
    }
}
