<?php

namespace App\Models;

use App\Services\Catalog\StockManager;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Route;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'category_id', 'brand_id', 'name', 'slug', 'description', 'meta_title', 'meta_description', 'price', 'price_max', 'compare_at_price', 'stock', 'sold_count',
    'rating', 'reviews_count', 'watchers_count', 'free_shipping', 'return_days', 'image', 'hover_image', 'hover_video',
    'badges', 'colors', 'variants_count', 'specifications', 'sale_starts_at', 'sale_ends_at', 'is_active', 'is_bundle',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, Searchable;

    protected static function booted(): void
    {
        // A product going on or off sale changes how many packs its variants allow (F-093).
        static::updated(function (Product $product): void {
            if ($product->wasChanged('is_active') && ! $product->is_bundle) {
                app(StockManager::class)->refreshPacksOf($product);
            }
        });
    }

    /**
     * Fields searched by the storefront search (F-022). With the database engine they must be product columns.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'description' => strip_tags((string) $this->description),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        // Not loaded yet right after creation: the column defaults to active.
        return $this->is_active ?? true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Whole FCFA.
            'price' => 'integer',
            'price_max' => 'integer',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
            'sold_count' => 'integer',
            'rating' => 'float',
            'free_shipping' => 'boolean',
            'badges' => 'array',
            'colors' => 'array',
            'specifications' => 'array',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'is_bundle' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('position');
    }

    /**
     * Components of a pack (F-093), in display order.
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id')->orderBy('position')->orderBy('id');
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderByDesc('is_default')->orderBy('position')->orderBy('id');
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function stockMovements(): HasManyThrough
    {
        return $this->hasManyThrough(StockMovement::class, ProductVariant::class);
    }

    /**
     * Refreshes the storefront summary kept on the product from its variants: total stock, cheapest price
     * (with its compare price), highest price for the "from … to …" range and number of variants.
     * Saved quietly: the change itself is already logged on the variant.
     */
    public function syncFromVariants(): void
    {
        $variants = $this->variants()->get(['price', 'compare_at_price', 'sale_starts_at', 'sale_ends_at', 'stock']);

        if ($variants->isEmpty()) {
            return;
        }

        // Prices as they apply right now (F-090); the scheduler runs this again when a sale window opens or closes.
        $cheapest = $variants->sortBy(fn (ProductVariant $variant) => $variant->currentPrice())->first();
        $highest = $variants->max(fn (ProductVariant $variant) => $variant->currentPrice());

        $this->forceFill([
            'stock' => $variants->sum('stock'),
            'price' => $cheapest->currentPrice(),
            'compare_at_price' => $cheapest->currentComparePrice(),
            'sale_starts_at' => $cheapest->hasSale() ? $cheapest->sale_starts_at : null,
            'sale_ends_at' => $cheapest->hasSale() ? $cheapest->sale_ends_at : null,
            'price_max' => $highest > $cheapest->currentPrice() ? $highest : null,
            'variants_count' => $variants->count() > 1 ? $variants->count() : 0,
        ])->saveQuietly();
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Public page of the product ("#" until the product page route is registered).
     */
    public function url(): string
    {
        return Route::has('products.show') ? route('products.show', $this) : '#';
    }

    public function isSoldOut(): bool
    {
        return $this->stock === 0;
    }

    public function hasLimitedStock(): bool
    {
        return ! $this->isSoldOut() && $this->stock <= config('storefront.product_card.limited_stock_threshold');
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    public function discountPercentage(): ?int
    {
        return $this->isOnSale()
            ? (int) round((1 - $this->price / $this->compare_at_price) * 100)
            : null;
    }

    /**
     * Share of the initial inventory still available, used by the "only N left" progress bar.
     */
    public function stockLeftPercentage(): int
    {
        $initial = $this->stock + $this->sold_count;

        return $initial === 0 ? 0 : (int) round($this->stock / $initial * 100);
    }

    /**
     * Countdown to the end of a running sale (F-094).
     */
    public function hasCountdown(): bool
    {
        return $this->isOnSale() && ($this->sale_ends_at?->isFuture() ?? false);
    }
}
