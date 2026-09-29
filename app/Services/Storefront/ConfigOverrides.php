<?php

namespace App\Services\Storefront;

use App\Models\Setting;
use Throwable;

/**
 * Store texts, images and menus edited in the back-office (F-111) laid over config/storefront.php and
 * config/navigation.php: every screen keeps reading config(), and an empty setting keeps the file's default.
 * Applied when the application starts (queued SMS and e-mails carry the store name) and again on every web
 * request (App\Http\Middleware\ApplyStoreSettings), so a change shows at once.
 */
class ConfigOverrides
{
    /** Setting key => config key, plain text. */
    public const TEXT = [
        'identity.name' => 'storefront.name',
        'identity.description' => 'storefront.description',
        'identity.about' => 'storefront.about',
        'identity.favicon' => 'storefront.favicon',
        'footer.banner' => 'storefront.footer_banner',
        'product_card.shipping_delay' => 'storefront.shipping.delay',
    ];

    /** Setting key => config key, a JSON list of words or sentences. */
    public const LISTS = [
        'announcements.trending' => 'storefront.announcements.trending',
        'announcements.campaign' => 'storefront.announcements.campaign',
        'search.popular' => 'storefront.search.popular',
        'search.placeholders' => 'storefront.search.placeholders',
    ];

    /** Menus edited under Contenus › Menus (JSON, see App\Filament\Pages\Menus). */
    public const MENUS = ['menu.pages', 'menu.help', 'menu.footer', 'menu.sidebar', 'menu.legal'];

    /** The payment methods of the footer, by setting key suffix (the order of config storefront.payment_methods). */
    public const PAYMENT_LOGOS = ['orange-money', 'mtn-momo', 'moov-money', 'wave', 'cash-on-delivery'];

    public static function apply(): void
    {
        try {
            $values = Setting::values();
        } catch (Throwable) {
            return; // No database or settings table yet (fresh install, build step, migrations running).
        }

        if ($values === []) {
            return;
        }

        foreach (self::TEXT as $key => $config) {
            if (filled($values[$key] ?? null)) {
                config([$config => $values[$key]]);
            }
        }

        foreach (self::LISTS as $key => $config) {
            if (is_array($list = self::decode($values[$key] ?? null)) && $list !== []) {
                config([$config => array_values($list)]);
            }
        }

        if (filled($values['identity.logo'] ?? null)) {
            config(['storefront.logo' => $values['identity.logo'], 'storefront.logo_small' => ImageOptimizer::smallVariant($values['identity.logo']) ?? $values['identity.logo']]);
        }

        if (filled($values['product_card.limited_stock_threshold'] ?? null)) {
            config(['storefront.product_card.limited_stock_threshold' => (int) $values['product_card.limited_stock_threshold']]);
        }

        config(['storefront.app_stores' => collect(config('storefront.app_stores'))
            ->map(fn (array $store) => [...$store, 'url' => $values['apps.'.str($store['label'])->slug()] ?? $store['url']])
            ->all()]);

        config(['storefront.payment_methods' => collect(config('storefront.payment_methods'))
            ->map(fn (array $method, int $index) => [...$method, 'logo' => $values['payment_logo.'.(self::PAYMENT_LOGOS[$index] ?? $index)] ?? $method['logo']])
            ->all()]);

        self::applyMenus($values);
    }

    /**
     * @param  array<string, string>  $values
     */
    private static function applyMenus(array $values): void
    {
        $main = config('navigation.main');

        foreach ($main as $index => $item) {
            if (($item['type'] ?? null) === 'mega' && is_array($columns = self::decode($values['menu.pages'] ?? null))) {
                $main[$index]['columns'] = $columns;
            }

            if (($item['type'] ?? null) === 'dropdown' && is_array($links = self::decode($values['menu.help'] ?? null))) {
                $main[$index]['links'] = $links;
            }
        }

        config(['navigation.main' => $main]);

        foreach (['footer', 'sidebar'] as $menu) {
            if (is_array($groups = self::decode($values["menu.{$menu}"] ?? null))) {
                config(["navigation.{$menu}" => collect($groups)->mapWithKeys(fn (array $group) => [$group['title'] => $group['links'] ?? []])->all()]);
            }
        }

        if (is_array($links = self::decode($values['menu.legal'] ?? null))) {
            config(['navigation.legal' => $links]);
        }
    }

    private static function decode(?string $json): mixed
    {
        if (blank($json)) {
            return null;
        }

        try {
            return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
    }
}
