<?php

namespace App\Services\Storefront;

use App\Jobs\SendMetaConversionEvent;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Meta Conversions API: the pixel's events also sent by the server, so that sales and carts still count when the
 * browser blocks the pixel and when CinetPay confirms a payment the customer never came back from. Each event
 * carries the same id as its browser twin, and Meta keeps one of the two. Only for visitors who accepted the
 * cookies; their e-mail, phone and name are hashed (SHA-256) before leaving, as Meta requires.
 */
class MetaConversions
{
    /** The store's events (GA4 names, as App\Services\Storefront\Analytics records them) in Meta's vocabulary. */
    public const EVENTS = [
        'view_item' => 'ViewContent',
        'add_to_cart' => 'AddToCart',
        'begin_checkout' => 'InitiateCheckout',
        'add_payment_info' => 'AddPaymentInfo',
        'purchase' => 'Purchase',
        'search' => 'Search',
        'add_to_wishlist' => 'AddToWishlist',
        'generate_lead' => 'Lead',
        'sign_up' => 'CompleteRegistration',
        'contact' => 'Contact',
    ];

    public function enabled(): bool
    {
        return filled(Setting::get('analytics.meta_pixel_id')) && filled(config('services.meta.conversions_token'));
    }

    /**
     * What the server knows of the visitor for Meta (IP, browser, Meta cookies, page), or null without consent.
     *
     * @return array<string, string>|null
     */
    public function context(Request $request): ?array
    {
        if ($request->cookie(Analytics::CONSENT_COOKIE) !== 'granted') {
            return null;
        }

        // Arrived from an ad but the pixel has not written its cookie yet: the click id is in the address.
        $fbc = $request->cookie('_fbc')
            ?: (filled($fbclid = $request->query('fbclid')) ? 'fb.1.'.now()->getTimestampMs().'.'.$fbclid : null);

        return array_filter([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $fbc,
            // A form posts from the page the visitor was on.
            'url' => $request->isMethod('GET') ? $request->fullUrl() : $request->headers->get('referer', $request->fullUrl()),
        ], filled(...));
    }

    /**
     * An event of the page being answered, sent with the visitor's context.
     *
     * @param  array<string, mixed>  $params  GA4 parameters (items, value, currency, search_term…)
     */
    public function send(string $event, array $params, string $eventId, Request $request): void
    {
        if (! isset(self::EVENTS[$event]) || ! $this->enabled() || ! $context = $this->context($request)) {
            return;
        }

        $this->dispatch($event, $params, $eventId, $context, $this->person($request->user()));
    }

    /**
     * The sale, once: at the order for cash on delivery, at CinetPay's confirmation for online payments, which may
     * come from CinetPay's server alone. Uses what was kept on the order when it was placed.
     */
    public function purchase(Order $order): void
    {
        if (! $this->enabled() || empty($order->tracking)) {
            return;
        }

        $order->loadMissing('items', 'user');
        [$first, $last] = array_pad(explode(' ', trim((string) $order->customer_name), 2), 2, null);

        $this->dispatch('purchase', Analytics::purchaseParams($order), Analytics::purchaseEventId($order), $order->tracking, [
            ...$this->person($order->user),
            'em' => $order->email ?: $order->user?->email,
            'ph' => $order->phone,
            'fn' => $first,
            'ln' => $last,
            'ct' => $order->destination_city ?: 'abidjan',
            'external_id' => $order->user_id ? 'user-'.$order->user_id : 'phone-'.self::phone($order->phone),
        ]);
    }

    /**
     * Facebook and Instagram read the store's events in their own words: products, quantities, value.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function customData(array $params): array
    {
        $items = $params['items'] ?? [];

        return array_filter([
            'currency' => $params['currency'] ?? null,
            'value' => $params['value'] ?? null,
            'content_type' => $items === [] ? null : 'product',
            'content_ids' => array_column($items, 'item_id') ?: null,
            'contents' => array_map(fn (array $item) => array_filter([
                'id' => $item['item_id'],
                'quantity' => $item['quantity'] ?? 1,
                'item_price' => $item['price'] ?? null,
            ], fn ($value) => $value !== null), $items) ?: null,
            'content_name' => count($items) === 1 ? $items[0]['item_name'] ?? null : null,
            'num_items' => $items === [] ? null : array_sum(array_map(fn (array $item) => $item['quantity'] ?? 1, $items)),
            'order_id' => $params['transaction_id'] ?? null,
            'search_string' => $params['search_term'] ?? null,
            'content_category' => $params['method'] ?? null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, string>  $context
     * @param  array<string, string|null>  $person
     */
    private function dispatch(string $event, array $params, string $eventId, array $context, array $person): void
    {
        SendMetaConversionEvent::dispatch((string) Setting::get('analytics.meta_pixel_id'), [
            'event_name' => self::EVENTS[$event],
            'event_time' => now()->getTimestamp(),
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $context['url'] ?? null,
            'user_data' => array_filter([
                'client_ip_address' => $context['ip'] ?? null,
                'client_user_agent' => $context['user_agent'] ?? null,
                'fbp' => $context['fbp'] ?? null,
                'fbc' => $context['fbc'] ?? null,
                'country' => [self::hash('ci')],
                ...$this->hashed($person),
            ]),
            'custom_data' => self::customData($params),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function person(?User $user): array
    {
        return $user ? ['em' => $user->email, 'ph' => $user->phone, 'external_id' => 'user-'.$user->id] : [];
    }

    /**
     * @param  array<string, string|null>  $person
     * @return array<string, list<string>>
     */
    private function hashed(array $person): array
    {
        if (isset($person['ph'])) {
            $person['ph'] = self::phone($person['ph']);
        }

        return collect($person)
            ->map(fn (?string $value) => filled($value) ? [self::hash($value)] : null)
            ->filter()
            ->all();
    }

    /** "07 01 02 03 04" or "+225 0701020304" → "2250701020304", as Meta matches phones (country code, digits). */
    private static function phone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return str_starts_with($digits, '225') ? $digits : '225'.$digits;
    }

    private static function hash(string $value): string
    {
        return hash('sha256', mb_strtolower(trim($value)));
    }
}
