<?php

namespace App\Http\Controllers;

use App\Models\Commune;
use App\Models\ProductVariant;
use App\Services\Cart\CartException;
use App\Services\Cart\CartManager;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Product pages send the chosen variant; product cards send the product (its single, default variant).
        $data = $request->validate([
            'variant_id' => ['required_without:product_id', 'integer', 'exists:product_variants,id'],
            'product_id' => ['required_without:variant_id', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartManager::MAX_QUANTITY],
        ]);

        $variant = isset($data['variant_id'])
            ? ProductVariant::with('product')->findOrFail($data['variant_id'])
            : ProductVariant::with('product')->where('product_id', $data['product_id'])->where('is_default', true)->firstOrFail();

        try {
            $inCart = $this->cart->add($variant, (int) ($data['quantity'] ?? 1));
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
}
