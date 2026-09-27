<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Account\AddressController;
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
        $addresses = $user?->addresses()->with('commune')->get() ?? collect();
        $default = $addresses->firstWhere('is_default', true);

        return view('pages.checkout', [
            'summary' => $summary,
            'communes' => Commune::deliverable()->with('zone')->get()->groupBy(fn (Commune $commune) => $commune->zone->name),
            'paymentMethods' => PaymentMethod::cases(),
            'addresses' => $addresses,
            // Default address first (F-071), then the account, then the commune chosen in the cart.
            'defaults' => [
                'customer_name' => $default?->recipient_name ?? $user?->name,
                'phone' => ($default?->phone ?? $user?->phone) ? PhoneNumber::format($default?->phone ?? $user->phone) : null,
                'email' => $user?->email,
                'commune_id' => $default?->commune_id ?? $summary->commune?->id,
                'district' => $default?->district,
                'landmark' => $default?->landmark,
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

        // "Enregistrer cette adresse": the delivery details join the customer's address book.
        if ($request->user() && $request->boolean('save_address') && $request->user()->addresses()->count() < AddressController::MAX_ADDRESSES) {
            $request->user()->addresses()->create([
                'label' => 'Adresse '.($request->user()->addresses()->count() + 1),
                'recipient_name' => $order->customer_name,
                'phone' => $order->phone,
                'commune_id' => $order->commune_id,
                'district' => $order->district,
                'landmark' => $order->landmark,
                'is_default' => $request->user()->addresses()->doesntExist(),
            ]);
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
