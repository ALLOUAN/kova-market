<?php

namespace App\Services\Checkout;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Events\OrderPlaced;
use App\Models\BundleItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Commune;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Catalog\InsufficientStock;
use App\Services\Catalog\StockManager;
use App\Services\Promotions\CouponException;
use App\Services\Promotions\CouponValidator;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

/**
 * Turns the cart into an order (F-050 to F-055), all or nothing: the stock of every line is taken under a
 * row lock (no overselling), prices are read again from the variants and frozen on the order lines, the promo
 * code is checked a last time and its use recorded (F-042), and the cart is emptied. If one line or the code
 * cannot be served, nothing is recorded and the customer goes back to the cart.
 */
class PlaceOrder
{
    public function __construct(
        private StockManager $stock,
        private OrderNumberGenerator $numbers,
        private CouponValidator $coupons,
    ) {}

    /**
     * @param  array{customer_name: string, phone: string, email: ?string, commune_id: int, district: string, landmark: ?string, note: ?string, payment_method: string, marketing_opt_in: bool}  $details
     *
     * @throws CheckoutException
     */
    public function handle(Cart $cart, array $details, ?User $user = null, string $source = 'web'): Order
    {
        $commune = Commune::with('zone')->find($details['commune_id']);

        if (! $commune?->isDeliverable()) {
            throw new CheckoutException('La livraison n’est pas disponible pour cette commune.');
        }

        $order = DB::transaction(function () use ($cart, $details, $user, $source, $commune): Order {
            $items = $cart->items()->with('variant.product.category', 'variant.attributeValues.attribute')->get();

            if ($items->isEmpty()) {
                throw new CheckoutException('Votre panier est vide.');
            }

            $number = $this->numbers->next();
            $lines = $items->map(fn (CartItem $item) => $this->takeStock($item, $number, $user));

            $subtotal = $lines->sum('line_total');
            $coupon = $cart->coupon_id ? Coupon::lockForUpdate()->find($cart->coupon_id) : null;
            $discount = $coupon ? $this->couponDiscount($coupon, $items, $details['phone'], $user) : 0;
            $regularShippingFee = $this->shippingFee($commune, $subtotal);
            $shippingFee = $coupon?->type === CouponType::FreeShipping ? 0 : $regularShippingFee;
            $total = $subtotal - $discount + $shippingFee;
            $method = PaymentMethod::from($details['payment_method']);
            $this->ensureMethodAllowed($method, $total);

            $order = Order::create([
                'number' => $number,
                'user_id' => $user?->getKey(),
                'status' => OrderStatus::Received,
                'payment_method' => $method,
                'payment_status' => PaymentStatus::Pending,
                'source' => $source,
                'customer_name' => $details['customer_name'],
                'phone' => $details['phone'],
                'email' => $details['email'] ?? null,
                'commune_id' => $commune->getKey(),
                'commune_name' => $commune->name,
                'zone_name' => $commune->zone->name,
                'district' => $details['district'],
                'landmark' => $details['landmark'] ?? null,
                'note' => $details['note'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount' => $discount,
                'coupon_code' => $coupon?->code,
                'total' => $total,
                'marketing_opt_in' => $details['marketing_opt_in'],
                'terms_accepted_at' => now(),
            ]);

            $order->items()->createMany($lines->all());
            $order->statusHistory()->create(['to_status' => OrderStatus::Received, 'user_id' => $user?->getKey()]);

            if ($coupon) {
                $coupon->increment('times_used');
                $coupon->usages()->create([
                    'order_id' => $order->getKey(),
                    'user_id' => $user?->getKey(),
                    'phone' => $order->phone,
                    'amount' => $discount + ($regularShippingFee - $shippingFee),
                ]);
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null]);

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    /**
     * Takes the line's units out of the stock and returns the frozen order line.
     *
     * @return array<string, mixed>
     */
    private function takeStock(CartItem $item, string $number, ?User $user): array
    {
        $variant = $item->variant;
        $product = $variant->product;

        if (! $product->is_active) {
            throw new CheckoutException("« {$product->name} » n’est plus en vente : retirez-le du panier pour commander.");
        }

        try {
            $this->stock->adjust($variant, -$item->quantity, StockMovementReason::Sale, $user, "Commande {$number}");
        } catch (InsufficientStock $exception) {
            throw new CheckoutException(
                $exception->variant->stock === 0
                    ? "« {$product->name} » vient d’être épuisé : retirez-le du panier pour commander."
                    : "Il ne reste que {$exception->variant->stock} « {$product->name} » : ajustez la quantité pour commander.",
            );
        }

        return [
            'product_variant_id' => $variant->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'variant_label' => $variant->attributeValues->isEmpty() ? null : $variant->label(),
            'bundle_contents' => $product->is_bundle ? $this->packContents($product) : null,
            'sku' => $variant->sku,
            'image' => $product->image,
            'unit_price' => $variant->currentPrice(),
            'quantity' => $item->quantity,
            'line_total' => $variant->currentPrice() * $item->quantity,
        ];
    }

    /**
     * What a pack holds, frozen on the order line (F-093): the pack may change later, the order must not.
     *
     * @return list<array{name: string, variant: ?string, sku: string, quantity: int}>
     */
    private function packContents(Product $pack): array
    {
        return $pack->bundleItems()->with('variant.product', 'variant.attributeValues.attribute')->get()
            ->map(fn (BundleItem $item) => [
                'name' => $item->variant->product->name,
                'variant' => $item->variant->attributeValues->isEmpty() ? null : $item->variant->label(),
                'sku' => $item->variant->sku,
                'quantity' => $item->quantity,
            ])
            ->values()
            ->all();
    }

    /**
     * Last check of the cart's promo code, the code row being locked: two orders cannot both take the
     * last allowed use. The per-customer cap is checked with the phone typed in the form.
     *
     * @param  EloquentCollection<int, CartItem>  $items
     */
    private function couponDiscount(Coupon $coupon, EloquentCollection $items, string $phone, ?User $user): int
    {
        $basket = $items->map(fn (CartItem $item) => ['product' => $item->variant->product, 'amount' => $item->variant->currentPrice() * $item->quantity]);

        try {
            return $this->coupons->discount($coupon, $basket->toBase(), $phone, $user);
        } catch (CouponException $exception) {
            throw new CheckoutException("Code promo {$coupon->code} : {$exception->getMessage()} Retirez-le du panier pour commander sans remise.");
        }
    }

    private function shippingFee(Commune $commune, int $subtotal): int
    {
        $threshold = Setting::get('delivery.free_shipping_threshold');

        return filled($threshold) && $subtotal >= (int) $threshold ? 0 : $commune->zone->fee;
    }

    /**
     * Cash on delivery is refused above the amount set in the store settings (F-063).
     */
    private function ensureMethodAllowed(PaymentMethod $method, int $total): void
    {
        $limit = Setting::get('payment.cash_on_delivery_limit');

        if ($method === PaymentMethod::CashOnDelivery && filled($limit) && $total > (int) $limit) {
            throw new CheckoutException('Le paiement à la livraison est limité à '.Money::format((int) $limit).' par commande.');
        }
    }
}
