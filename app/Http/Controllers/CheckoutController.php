<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Commune;
use App\Models\Order;
use App\Services\Cart\CartManager;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\PlaceOrder;
use App\Services\Payments\CinetPayException;
use App\Services\Payments\OnlinePayments;
use App\Services\Storefront\Analytics;
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

    public function show(Request $request, Analytics $analytics): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if ($summary->count() === 0) {
            return redirect()->route('cart.show')->with('cart_error', 'Votre panier est vide ou ses articles ne sont plus disponibles.');
        }

        // A code that no longer applies is settled in the cart, where its cause is shown.
        if ($summary->couponIssue) {
            return redirect()->route('cart.show')->with('cart_error', "Le code promo {$summary->coupon->code} ne s’applique plus : retirez-le ou complétez votre panier pour commander.");
        }

        $analytics->beginCheckout($summary);

        $user = $request->user();
        $addresses = $user?->addresses()->with('commune')->get() ?? collect();
        $default = $addresses->firstWhere('is_default', true);

        return view('pages.checkout', [
            'summary' => $summary,
            'communes' => Commune::deliverable()->with('zone')->get()->groupBy(fn (Commune $commune) => $commune->zone->name),
            'paymentMethods' => PaymentMethod::available(),
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

    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder, Analytics $analytics, OnlinePayments $payments): RedirectResponse
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

        if ($request->boolean('save_address')) {
            $request->user()?->saveAddressFromOrder($order);
        }

        $request->session()->push(self::PLACED, $order->number);

        // Paid online: straight to CinetPay's page; the purchase is reported once the payment is confirmed.
        if ($order->payment_method->isOnline()) {
            try {
                return redirect()->away($payments->start($order)->payment_url);
            } catch (CinetPayException $exception) {
                return redirect()->route('checkout.confirmation', $order)->with('payment_error', $exception->getMessage());
            }
        }

        // Reported once, on the confirmation page that follows (a reload does not count it again).
        $analytics->purchase($order->load('items'));

        return redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Request $request, Order $order): View
    {
        abort_unless(self::isVisibleTo($request, $order), 404);

        // A payment under way is checked with CinetPay on each (self-refreshing) visit, the notification may be late.
        if ($order->awaitsOnlinePayment() && $attempt = $order->payments()->whereIn('status', [TransactionStatus::Initiated, TransactionStatus::Pending])->first()) {
            try {
                app(OnlinePayments::class)->synchronize($attempt, 'confirmation');
                $order->refresh();
            } catch (CinetPayException) {
                // CinetPay does not answer now: the page keeps waiting.
            }
        }

        return view('pages.order-confirmation', [
            'order' => $order->load('items'),
            'paymentTimeout' => app(OnlinePayments::class)->timeoutMinutes(),
        ]);
    }

    /**
     * Only the visitor who placed the order (in this session) or its account sees its confirmation and pays it.
     */
    public static function isVisibleTo(Request $request, Order $order): bool
    {
        return in_array($order->number, $request->session()->get(self::PLACED, []), true)
            || ($order->user_id !== null && $order->user_id === $request->user()?->getKey());
    }
}
