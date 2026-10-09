<?php

namespace App\Services\Storefront;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\Cart\CartLine;
use App\Services\Cart\CartSummary;
use Illuminate\Support\Str;

/**
 * Audience measurement (F-155, F-156): Google Analytics 4, Meta and TikTok pixels, identifiers set in the store
 * settings. The page only carries the identifiers and its e-commerce events; public/assets/js/analytics.js loads
 * the trackers once the visitor accepts, never before. Pages add their events to the current request, redirects
 * hand theirs to the next page through the session, and answers to background requests (add to cart, favourites,
 * newsletter) carry theirs. Each event has an id, also given to its copy sent by the server to Meta
 * (MetaConversions), so that Meta counts it once.
 */
class Analytics
{
    public const CONSENT_COOKIE = 'kova_consent';

    private const FLASH = 'analytics_events';

    public function __construct(private MetaConversions $conversions) {}

    /**
     * @return array{ga4: ?string, meta: ?string, tiktok: ?string}
     */
    public function trackers(): array
    {
        return [
            'ga4' => Setting::get('analytics.ga4_id') ?: null,
            'meta' => Setting::get('analytics.meta_pixel_id') ?: null,
            'tiktok' => Setting::get('analytics.tiktok_pixel_id') ?: null,
        ];
    }

    public function enabled(): bool
    {
        return array_filter($this->trackers()) !== [];
    }

    /** Texts of the cookie banner, editable in Paramètres › Audience. */
    public const BANNER_TEXTS = [
        'title' => 'Nous respectons votre vie privée',
        'message' => 'Avec votre accord, nous mesurons la fréquentation du site et l’efficacité de nos publicités. Sans accord, rien de tout cela n’est chargé.',
        'accept' => 'Accepter',
        'decline' => 'Refuser',
    ];

    /**
     * Whether the cookie banner (and the footer's "Gérer les cookies") is shown: always while a tracker is set —
     * consent is then required — and otherwise as chosen in the back-office (shown by default).
     */
    public function showsConsentBanner(): bool
    {
        return $this->enabled() || Setting::get('cookies.always', '1') !== '0';
    }

    public function bannerText(string $key): string
    {
        return (string) (Setting::get("cookies.{$key}") ?: self::BANNER_TEXTS[$key]);
    }

    public function searchConsoleToken(): ?string
    {
        return Setting::get('analytics.search_console_token') ?: null;
    }

    /**
     * Adds an event to the page being rendered.
     *
     * @param  array<string, mixed>  $params
     */
    public function track(string $name, array $params): void
    {
        $event = $this->event($name, $params);

        // Kept on the request itself, so an instance outliving a request (tests, workers) never mixes pages.
        request()->attributes->set(self::FLASH, [...request()->attributes->get(self::FLASH, []), $event]);
    }

    /**
     * Hands an event to the next page (an action that redirects, e.g. adding to the cart).
     *
     * @param  array<string, mixed>  $params
     */
    public function trackOnNextPage(string $name, array $params, ?string $id = null, bool $toServer = true): void
    {
        if (! $this->enabled()) {
            return;
        }

        session()->flash(self::FLASH, [...session()->get(self::FLASH, []), $this->event($name, $params, $id, $toServer)]);
    }

    /**
     * Adds an event to the page, or to the next one when the action redirects, or to the answer of a background
     * request (see currentEvents()).
     *
     * @param  array<string, mixed>  $params
     */
    public function trackAction(string $name, array $params): void
    {
        request()->expectsJson() ? $this->track($name, $params) : $this->trackOnNextPage($name, $params);
    }

    /**
     * @return list<array{name: string, id: string, params: array<string, mixed>}>
     */
    public function events(): array
    {
        return [...session()->get(self::FLASH, []), ...$this->currentEvents()];
    }

    /**
     * The events of this request only, for the answer of a background request (public/assets/js/analytics.js
     * sends them with window.kovaTrack).
     *
     * @return list<array{name: string, id: string, params: array<string, mixed>}>
     */
    public function currentEvents(): array
    {
        return $this->enabled() ? request()->attributes->get(self::FLASH, []) : [];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{name: string, id: string, params: array<string, mixed>}
     */
    private function event(string $name, array $params, ?string $id = null, bool $toServer = true): array
    {
        $id ??= (string) Str::uuid();

        if ($toServer) {
            $this->conversions->send($name, $params, $id, request());
        }

        return ['name' => $name, 'id' => $id, 'params' => $params];
    }

    /**
     * A product list seen on the page (home selections): which products were shown, in which list and position.
     *
     * @param  iterable<Product>  $products
     */
    public function viewItemList(string $listId, string $listName, iterable $products): void
    {
        $items = collect($products)->values()
            ->map(fn (Product $product, int $index) => [...self::listItem($product), 'index' => $index, 'item_list_id' => $listId, 'item_list_name' => $listName])
            ->all();

        if ($items !== []) {
            $this->track('view_item_list', ['item_list_id' => $listId, 'item_list_name' => $listName, 'items' => $items]);
        }
    }

    /**
     * Home banners seen on the page (GA4 "promotions"): which banner, in which slot.
     *
     * @param  iterable<array<string, mixed>>  $banners  banners as given to the home page partials
     */
    public function viewPromotions(iterable $banners): void
    {
        $promotions = collect($banners)->filter()->map(fn (array $banner) => self::promotion($banner))->values()->all();

        if ($promotions !== []) {
            $this->track('view_promotion', ['items' => $promotions]);
        }
    }

    /**
     * What a product card carries for "select_item" when clicked (public/assets/js/analytics.js).
     *
     * @return array<string, mixed>
     */
    public static function listItem(Product $product): array
    {
        return array_filter([
            // The default variant's reference, as the other e-commerce events, when loaded; else the product id.
            'item_id' => $product->relationLoaded('defaultVariant') && $product->defaultVariant ? $product->defaultVariant->sku : (string) $product->id,
            'item_name' => $product->name,
            'item_brand' => $product->relationLoaded('brand') ? $product->brand?->name : null,
            'item_category' => $product->relationLoaded('category') ? $product->category?->name : null,
            'price' => $product->price,
        ], fn ($value) => $value !== null);
    }

    /**
     * What a home banner carries for "view_promotion" and "select_promotion".
     *
     * @param  array<string, mixed>  $banner
     * @return array<string, string>
     */
    public static function promotion(array $banner): array
    {
        return array_filter([
            'promotion_id' => isset($banner['id']) ? 'banner-'.$banner['id'] : null,
            'promotion_name' => trim(($banner['highlight'] ?? '').' '.($banner['title'] ?? '')) ?: null,
            'creative_name' => isset($banner['image']) ? basename((string) $banner['image']) : null,
            'creative_slot' => $banner['placement'] ?? null,
        ]);
    }

    public function viewItem(Product $product): void
    {
        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

        $this->track('view_item', $this->value([$this->item($product, $variant, 1)]));
    }

    public function addToCart(ProductVariant $variant, int $quantity): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->trackAction('add_to_cart', $this->value([$this->item($variant->product, $variant, $quantity)]));
    }

    public function addToWishlist(Product $product): void
    {
        if (! $this->enabled()) {
            return;
        }

        $product->loadMissing('defaultVariant.attributeValues', 'brand', 'category');
        $this->trackAction('add_to_wishlist', $this->value([$this->item($product, $product->defaultVariant, 1)]));
    }

    /**
     * A search of the catalogue, with the number of products found.
     */
    public function search(string $term, int $results): void
    {
        $this->track('search', ['search_term' => $term, 'results' => $results]);
    }

    /**
     * A newsletter sign-up ("Lead" for Meta).
     */
    public function lead(string $source): void
    {
        if ($this->enabled()) {
            $this->trackAction('generate_lead', ['method' => 'newsletter-'.$source]);
        }
    }

    /**
     * A customer account created ("CompleteRegistration" for Meta), once signed in: the account's e-mail and phone
     * help Meta recognise them.
     */
    public function signUp(): void
    {
        if ($this->enabled()) {
            $this->trackOnNextPage('sign_up', ['method' => 'site']);
        }
    }

    /**
     * A message to the store (contact form; WhatsApp clicks are reported by the browser alone).
     */
    public function contact(string $method): void
    {
        if ($this->enabled()) {
            $this->trackOnNextPage('contact', ['method' => $method]);
        }
    }

    /**
     * Leaving for CinetPay's page: known to the server only, the browser goes straight to CinetPay.
     */
    public function addPaymentInfo(Order $order): void
    {
        $this->conversions->send('add_payment_info', [...self::purchaseParams($order), 'payment_type' => 'cinetpay'], 'payment-info-'.$order->number, request());
    }

    public function beginCheckout(CartSummary $summary): void
    {
        $items = $summary->lines->filter->available
            ->map(fn (CartLine $line) => $this->item($line->product, $line->variant, $line->quantity))
            ->values()->all();

        $this->track('begin_checkout', [...$this->value($items), 'coupon' => $summary->couponApplies() ? $summary->coupon->code : null]);
    }

    /**
     * The sale on the next page. Its server copy goes through MetaConversions::purchase(), with the same id.
     */
    public function purchase(Order $order): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->trackOnNextPage('purchase', self::purchaseParams($order), self::purchaseEventId($order), toServer: false);
    }

    /** The same for the browser and the server, so that Meta counts the sale once. */
    public static function purchaseEventId(Order $order): string
    {
        return 'purchase-'.$order->number;
    }

    /**
     * @return array<string, mixed>
     */
    public static function purchaseParams(Order $order): array
    {
        return [
            'transaction_id' => $order->number,
            'currency' => config('storefront.currency'),
            'value' => $order->total,
            'shipping' => $order->shipping_fee,
            'coupon' => $order->coupon_code,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'item_id' => $item->sku,
                'item_name' => $item->product_name,
                'item_variant' => $item->variant_label,
                ...($item->saleQuantity()->unit->isMeasured()
                    ? ['price' => $item->line_total, 'quantity' => 1]
                    : ['price' => $item->unit_price, 'quantity' => $item->quantity]),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(Product $product, ?ProductVariant $variant, int $quantity): array
    {
        $price = $variant?->currentPrice() ?? $product->price;
        $rules = $product->saleQuantity();

        // GA4 counts whole items: a quantity sold by weight or volume is one item at the price of that quantity.
        if ($rules->unit->isMeasured()) {
            [$price, $quantity] = [$rules->lineTotal($price, max($rules->minimum(), $quantity)), 1];
        }

        return array_filter([
            'item_id' => $variant?->sku ?? (string) $product->id,
            'item_name' => $product->name,
            'item_brand' => $product->brand?->name,
            'item_category' => $product->category?->name,
            'item_variant' => $variant && $variant->attributeValues->isNotEmpty() ? $variant->label() : null,
            'price' => $price,
            'quantity' => $quantity,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function value(array $items): array
    {
        return [
            'currency' => config('storefront.currency'),
            'value' => collect($items)->sum(fn (array $item) => $item['price'] * $item['quantity']),
            'items' => $items,
        ];
    }
}
