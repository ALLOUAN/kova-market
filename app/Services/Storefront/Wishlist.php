<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Favourite products: kept on the account of a signed-in customer, on a year-long cookie token for a visitor, and
 * moved from the visitor's to the account at sign-in (App\Listeners\MergeWishlistOnLogin).
 */
class Wishlist
{
    public const COOKIE = 'kova_wishlist';

    public const LIFETIME_DAYS = 365;

    /** A visitor token created during this request, before its cookie reaches the browser. */
    private ?string $newVisitor = null;

    /** @var list<int>|null favourite product ids, read once per request (scoped service) */
    private ?array $ids = null;

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return $this->ids ??= $this->owned() ? $this->query()->pluck('product_id')->map(fn ($id) => (int) $id)->all() : [];
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
     * The favourites still on sale, most recently added first.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        if (! $this->owned()) {
            return new Collection;
        }

        return Product::query()->active()->with('category')
            ->joinSub($this->query()->select('product_id', 'created_at as added_at'), 'wishlist', 'wishlist.product_id', '=', 'products.id')
            ->orderByDesc('wishlist.added_at')
            ->select('products.*')
            ->get();
    }

    /**
     * Adds the product, or removes it if already there. Returns whether it is now a favourite.
     */
    public function toggle(Product $product): bool
    {
        $this->ids = null;
        $existing = $this->owned() ? $this->query()->where('product_id', $product->getKey())->first() : null;

        if ($existing) {
            $existing->delete();

            return false;
        }

        WishlistItem::create([...$this->owner(create: true), 'product_id' => $product->getKey()]);

        return true;
    }

    /**
     * At sign-in: the visitor's favourites join the account (without duplicates), and the cookie is dropped.
     */
    public function mergeInto(User $user, ?string $visitor): void
    {
        if (! is_string($visitor) || ! Str::isUuid($visitor)) {
            return;
        }

        $already = WishlistItem::where('user_id', $user->getKey())->pluck('product_id');

        WishlistItem::where('visitor', $visitor)->whereIn('product_id', $already)->delete();
        WishlistItem::where('visitor', $visitor)->update(['user_id' => $user->getKey(), 'visitor' => null]);

        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    private function owned(): bool
    {
        return auth()->check() || $this->visitor() !== null;
    }

    /**
     * @return Builder<WishlistItem>
     */
    private function query(): Builder
    {
        return WishlistItem::query()->where($this->owner());
    }

    /**
     * @return array{user_id?: int, visitor?: string}
     */
    private function owner(bool $create = false): array
    {
        if ($user = auth()->user()) {
            return ['user_id' => $user->getKey()];
        }

        $visitor = $this->visitor();

        if ($visitor === null && $create) {
            $visitor = $this->newVisitor = (string) Str::uuid();
            Cookie::queue(Cookie::make(self::COOKIE, $visitor, self::LIFETIME_DAYS * 24 * 60, httpOnly: true, sameSite: 'lax'));
        }

        return ['visitor' => $visitor];
    }

    private function visitor(): ?string
    {
        $token = $this->newVisitor ?? request()->cookie(self::COOKIE);

        return is_string($token) && Str::isUuid($token) ? $token : null;
    }
}
