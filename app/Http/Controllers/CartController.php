<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Models\Commune;
use App\Models\Coupon;
use App\Services\Cart\CartException;
use App\Services\Cart\CartManager;
use App\Services\Promotions\CouponException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cart page and cart changes (F-040 to F-044). Plain form posts: every change redirects with a message.
 */
class CartController extends Controller
{
    public function __construct(private CartManager $cart) {}

    public function show(): View
    {
        return view('pages.cart', [
            'summary' => $this->cart->summary(),
            'communes' => Commune::deliverable()->with('zone')->get()->groupBy(fn (Commune $commune) => $commune->zone->name),
            'publicCoupons' => Coupon::listed()->get(),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $variant = $request->variant();

        try {
            $inCart = $this->cart->add($variant, $request->quantity());
        } catch (CartException $exception) {
            return back()->with('cart_error', $exception->getMessage());
        }

        // "Acheter maintenant" goes straight to the cart, where the order will be placed (F-015).
        $redirect = $request->boolean('buy_now') ? redirect()->route('cart.show') : back();

        return $redirect
            ->with('cart_status', "« {$variant->product->name} » a été ajouté au panier ({$inCart} au total).")
            // Product cards ask the next page to slide the mini-cart open (storefront.product_card.cart_action).
            ->with('cart_open', $request->input('open') === 'sidenav');
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartManager::MAX_QUANTITY]]);
        $line = $this->cart->findItem($item) ?? abort(404);

        $kept = $this->cart->update($line, (int) $data['quantity']);

        return redirect()->route('cart.show')->with('cart_status', match (true) {
            $kept === 0 => 'L’article a été retiré du panier.',
            $kept < (int) $data['quantity'] => "Quantité ramenée à {$kept} : c’est le stock disponible.",
            default => 'Quantité mise à jour.',
        });
    }

    public function destroy(int $item): RedirectResponse
    {
        $this->cart->remove($this->cart->findItem($item) ?? abort(404));

        return redirect()->route('cart.show')->with('cart_status', 'L’article a été retiré du panier.');
    }

    public function commune(Request $request): RedirectResponse
    {
        $data = $request->validate(['commune_id' => ['required', 'integer', 'exists:communes,id']]);

        try {
            $this->cart->chooseCommune(Commune::with('zone')->findOrFail($data['commune_id']));
        } catch (CartException $exception) {
            return redirect()->route('cart.show')->with('cart_error', $exception->getMessage());
        }

        return redirect()->route('cart.show');
    }

    /**
     * "Code promo" (F-042): one code per cart, each refusal gives its cause.
     */
    public function applyCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']], [], ['code' => 'code promo']);
        $coupon = Coupon::findByCode($data['code']);

        if (! $coupon) {
            return redirect()->route('cart.show')->withInput()->with('coupon_error', 'Ce code promo n’existe pas.');
        }

        try {
            $this->cart->applyCoupon($coupon);
        } catch (CartException|CouponException $exception) {
            return redirect()->route('cart.show')->withInput()->with('coupon_error', $exception->getMessage());
        }

        return redirect()->route('cart.show')->with('cart_status', "Le code promo {$coupon->code} a été appliqué.");
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cart->removeCoupon();

        return redirect()->route('cart.show')->with('cart_status', 'Le code promo a été retiré.');
    }
}
