<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "N personnes ont vu ce produit ces 15 dernières minutes": real visits of the product page, counted per visitor
 * (a keyed hash of the session: nothing personal is stored) over a short window. The count is kept on the product
 * (watchers_count) by `catalog:refresh-viewers`, so product cards read it without extra queries; below the threshold
 * set in the back-office, or when the badge is switched off, it is empty and no badge shows.
 */
class ProductViewers
{
    public const WINDOW_MINUTES = 15;

    public const DEFAULT_MINIMUM = 3;

    /** Search engines and link previews are not people. */
    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|headless|lighthouse/i';

    public function record(Product $product, Request $request): void
    {
        if (! $request->hasSession() || preg_match(self::BOTS, (string) $request->userAgent())) {
            return;
        }

        DB::table('product_viewers')->upsert(
            [['product_id' => $product->getKey(), 'visitor' => hash_hmac('sha256', $request->session()->getId(), (string) config('app.key')), 'seen_at' => now()]],
            ['product_id', 'visitor'],
            ['seen_at'],
        );
    }

    public static function enabled(): bool
    {
        return Setting::get('product_card.viewers_enabled', '1') === '1';
    }

    public static function minimum(): int
    {
        return max(2, (int) Setting::get('product_card.viewers_minimum', self::DEFAULT_MINIMUM));
    }

    /**
     * Counts the visitors of the window, keeps the products at or above the threshold and forgets older visits.
     *
     * @return int products showing the badge
     */
    public function refresh(): int
    {
        $since = now()->subMinutes(self::WINDOW_MINUTES);

        DB::table('product_viewers')->where('seen_at', '<', $since)->delete();

        $counts = self::enabled()
            ? DB::table('product_viewers')->select('product_id', DB::raw('count(*) as viewers'))->groupBy('product_id')
                ->having('viewers', '>=', self::minimum())->pluck('viewers', 'product_id')
            : collect();

        Product::query()->whereNotNull('watchers_count')->whereNotIn('id', $counts->keys())->update(['watchers_count' => null]);

        foreach ($counts as $productId => $viewers) {
            Product::query()->whereKey($productId)->where(fn ($query) => $query->whereNull('watchers_count')->orWhere('watchers_count', '!=', $viewers))
                ->update(['watchers_count' => $viewers]);
        }

        return $counts->count();
    }
}
