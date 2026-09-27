<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Commune;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The visitor's cart (F-040 to F-044): a guest cart is found through a 30-day cookie, a signed-in customer's
 * cart through the account, and the guest cart joins the account at sign-in. Quantities never exceed the stock.
 */
class CartManager
{
    public const COOKIE = 'kova_cart';

    public const MAX_QUANTITY = 99;

    private ?Cart $cart = null;

    /** Request the cached cart belongs to: the instance may outlive a request (tests, long-running workers). */
    private ?int $resolvedFor = null;

    public function current(): ?Cart
    {
        if ($this->resolvedFor !== spl_object_id($this->request())) {
            $this->cart = $this->find();
            $this->resolvedFor = spl_object_id($this->request());
        }

        return $this->cart;
    }

    public function currentOrCreate(): Cart
    {
        if ($cart = $this->current()) {
            return $cart;
        }

        $this->cart = Cart::create([
            'token' => (string) Str::uuid(),
            'user_id' => $this->request()->user()?->getKey(),
            'expires_at' => now()->addDays(Cart::LIFETIME_DAYS),
        ]);
        $this->rememberInCookie($this->cart);

        return $this->cart;
    }

    /**
     * Adds units of a variant; returns the quantity now in the cart (capped to the stock).
     *
     * @throws CartException
     */
    public function add(ProductVariant $variant, int $quantity): int
    {
        $variant->loadMissing('product');

        if (! $variant->product->is_active) {
            throw new CartException('Ce produit n’est plus en vente.');
        }

        if ($variant->stock === 0) {
            throw new CartException('Ce produit est épuisé.');
        }

        $cart = $this->currentOrCreate();
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->getKey()]);
        $item->quantity = $this->cap(($item->exists ? $item->quantity : 0) + max(1, $quantity), $variant);
        $item->save();

        $this->touch($cart);

        return $item->quantity;
    }

    /**
     * Sets a line quantity (0 removes the line); returns the quantity kept.
     */
    public function update(CartItem $item, int $quantity): int
    {
        if ($quantity <= 0) {
            $this->remove($item);

            return 0;
        }

        $item->update(['quantity' => $this->cap($quantity, $item->variant)]);
        $this->touch($item->cart);

        return $item->quantity;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
        $this->touch($item->cart);
    }

    /**
     * A line of the visitor's own cart, or null (a line of another cart is never reachable).
     */
    public function findItem(int $itemId): ?CartItem
    {
        return $this->current()?->items()->whereKey($itemId)->first();
    }

    public function chooseCommune(Commune $commune): void
    {
        if (! $commune->isDeliverable()) {
            throw new CartException('La livraison n’est pas disponible pour cette commune.');
        }

        $cart = $this->currentOrCreate();
        $cart->update(['commune_id' => $commune->getKey()]);
        $this->touch($cart);
    }

    /**
     * At sign-in, the guest cart joins the customer's cart (quantities added, capped to the stock).
     */
    public function mergeGuestCartInto(User $user): void
    {
        $guest = $this->guestCart();

        if (! $guest) {
            return;
        }

        DB::transaction(function () use ($guest, $user): void {
            $owned = Cart::where('user_id', $user->getKey())->first();

            if (! $owned) {
                $guest->update(['user_id' => $user->getKey(), 'expires_at' => now()->addDays(Cart::LIFETIME_DAYS)]);

                return;
            }

            foreach ($guest->items()->with('variant')->get() as $item) {
                $line = $owned->items()->firstOrNew(['product_variant_id' => $item->product_variant_id]);
                $line->quantity = $this->cap(($line->exists ? $line->quantity : 0) + $item->quantity, $item->variant);
                $line->save();
            }

            $owned->update(['commune_id' => $owned->commune_id ?? $guest->commune_id]);
            $guest->delete();
        });

        $this->resolvedFor = null;
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    public function summary(): CartSummary
    {
        $cart = $this->current();
        $cart?->load(['items.variant.product', 'items.variant.attributeValues.attribute', 'commune.zone']);

        $lines = collect($cart?->items ?? [])->map(fn (CartItem $item) => new CartLine($item));
        $subtotal = $lines->sum(fn (CartLine $line) => $line->total());

        $threshold = Setting::get('delivery.free_shipping_threshold');
        $threshold = filled($threshold) ? (int) $threshold : null;
        $commune = $cart?->commune?->isDeliverable() ? $cart->commune : null;
        $free = $threshold !== null && $subtotal >= $threshold && $subtotal > 0;

        return new CartSummary(
            cart: $cart,
            lines: $lines,
            subtotal: $subtotal,
            commune: $commune,
            shippingFee: $commune ? ($free ? 0 : $commune->zone->fee) : null,
            freeShipping: $free,
            freeShippingThreshold: $threshold,
        );
    }

    private function find(): ?Cart
    {
        if ($user = $this->request()->user()) {
            return Cart::where('user_id', $user->getKey())->first();
        }

        return $this->guestCart();
    }

    private function guestCart(): ?Cart
    {
        $token = $this->request()->cookie(self::COOKIE);

        return is_string($token) && Str::isUuid($token)
            ? Cart::where('token', $token)->whereNull('user_id')->where('expires_at', '>=', now())->first()
            : null;
    }

    private function request(): Request
    {
        return request();
    }

    private function cap(int $quantity, ProductVariant $variant): int
    {
        return max(1, min($quantity, $variant->stock, self::MAX_QUANTITY));
    }

    private function touch(Cart $cart): void
    {
        $cart->update(['expires_at' => now()->addDays(Cart::LIFETIME_DAYS)]);

        if (! $cart->user_id) {
            $this->rememberInCookie($cart);
        }
    }

    private function rememberInCookie(Cart $cart): void
    {
        Cookie::queue(Cookie::make(self::COOKIE, $cart->token, Cart::LIFETIME_DAYS * 24 * 60, httpOnly: true, sameSite: 'lax'));
    }
}
