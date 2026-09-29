<?php

namespace App\Services\Storefront;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Product comparison: up to four products kept in the visitor's session (a short, per-visit choice: nothing stored
 * for anyone else), compared side by side on /comparer.
 */
class Comparison
{
    public const MAX = 4;

    private const SESSION_KEY = 'compare';

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', session()->get(self::SESSION_KEY, [])));
    }

    public function has(Product|int $product): bool
    {
        return in_array($product instanceof Product ? $product->getKey() : $product, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /**
     * Adds the product, or removes it if already there. Null when the list is full.
     */
    public function toggle(Product $product): ?bool
    {
        $ids = $this->ids();

        if (in_array($product->getKey(), $ids, true)) {
            session()->put(self::SESSION_KEY, array_values(array_diff($ids, [$product->getKey()])));

            return false;
        }

        if (count($ids) >= self::MAX) {
            return null;
        }

        session()->put(self::SESSION_KEY, [...$ids, $product->getKey()]);

        return true;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * The compared products still on sale, in the order they were added.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return new Collection;
        }

        return Product::query()->active()->with(['category', 'brand', 'variants.attributeValues.attribute'])->whereKey($ids)->get()
            ->sortBy(fn (Product $product) => array_search($product->getKey(), $ids, true))
            ->values();
    }

    /**
     * The specification labels of the compared products, in order of first appearance: one row each.
     *
     * @param  Collection<int, Product>  $products
     * @return list<string>
     */
    public static function specificationLabels(Collection $products): array
    {
        return $products->flatMap(fn (Product $product) => collect($product->specifications ?? [])->pluck('label'))
            ->filter()
            ->map(fn ($label) => trim((string) $label))
            ->unique(fn (string $label) => mb_strtolower($label))
            ->values()
            ->all();
    }

    /**
     * The value of a specification for a product ("—" when it has none).
     */
    public static function specification(Product $product, string $label): string
    {
        $row = collect($product->specifications ?? [])->first(fn ($spec) => mb_strtolower(trim((string) ($spec['label'] ?? ''))) === mb_strtolower($label));

        return filled($row['value'] ?? null) ? (string) $row['value'] : '—';
    }
}
