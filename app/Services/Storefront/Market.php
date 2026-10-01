<?php

namespace App\Services\Storefront;

use App\Enums\SaleUnit;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "Mon Marché", the market showcase (/mon-marche): a department of the catalog (by default the one created by
 * migration, slug "mon-marche") shown as a page of its own — banner, its categories as shortcuts and a row of
 * products per category. Texts, banner, department and size are set in Paramètres › Mon Marché.
 */
class Market
{
    public const DEFAULT_SLUG = 'mon-marche';

    public const DEFAULT_PER_ROW = 8;

    public function category(): ?Category
    {
        return once(function (): ?Category {
            $id = Setting::get('market.category_id');

            return ($id ? Category::whereNull('parent_id')->find($id) : null)
                ?? Category::whereNull('parent_id')->where('slug', self::DEFAULT_SLUG)->first();
        });
    }

    public function enabled(): bool
    {
        return Setting::get('market.enabled', '1') !== '0' && $this->category() !== null;
    }

    /**
     * Whether the category is the market department: its address is then the showcase. Read from the settings
     * alone (no query): it runs for every category link of the menus.
     */
    public function isMarket(Category $category): bool
    {
        if ($category->parent_id !== null || Setting::get('market.enabled', '1') === '0') {
            return false;
        }

        $id = Setting::get('market.category_id');

        return $id ? (int) $id === $category->getKey() : $category->slug === self::DEFAULT_SLUG;
    }

    /**
     * The market's categories with their sub-categories, for the "Mon Marché" mega menu (same layout as "Boutique").
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    public function menuTree(): \Illuminate\Database\Eloquent\Collection
    {
        return once(fn () => $this->category()?->children()->with('children')->get() ?? new \Illuminate\Database\Eloquent\Collection);
    }

    public function showInMenu(): bool
    {
        return $this->enabled() && Setting::get('market.in_menu', '1') !== '0';
    }

    public function title(): string
    {
        return (string) Setting::get('market.title', $this->category()?->name ?? 'Mon Marché');
    }

    public function subtitle(): string
    {
        return (string) Setting::get('market.subtitle', $this->category()?->tagline ?: 'Fruits, légumes, viandes, épicerie et boissons, livrés chez vous.');
    }

    public function banner(): ?string
    {
        return Setting::get('market.banner');
    }

    public function perRow(): int
    {
        return max(4, min(12, (int) Setting::get('market.per_row', self::DEFAULT_PER_ROW)));
    }

    /**
     * The department's categories, each with its best products (in stock first, then the best sellers),
     * the ones without products last.
     *
     * @return Collection<int, array{category: Category, products: Collection<int, Product>, count: int}>
     */
    public function rows(): Collection
    {
        $market = $this->category();

        if (! $market) {
            return collect();
        }

        return $market->children()->with('children')->get()
            ->map(function (Category $category): array {
                $query = Product::query()->active()->whereIn('category_id', $category->descendantIds());

                return [
                    'category' => $category,
                    'count' => (clone $query)->count(),
                    'products' => $query->with('category')
                        ->orderByRaw('stock = 0')->orderByDesc('sold_count')->orderByDesc('id')
                        ->limit($this->perRow())->get(),
                ];
            })
            ->sortBy(fn (array $row) => $row['count'] === 0)
            ->values();
    }

    /** Products of the whole department sold by weight or volume, for the "au poids" shortcut. */
    public function weighedProductsCount(): int
    {
        $market = $this->category();

        return $market ? Product::query()->active()->whereIn('category_id', $market->descendantIds())
            ->tap(self::weighed(...))->count() : 0;
    }

    /** @param  Builder<Product>  $query */
    public static function weighed(Builder $query): Builder
    {
        return $query->whereIn('sale_unit', [SaleUnit::Kilogram->value, SaleUnit::Litre->value]);
    }
}
