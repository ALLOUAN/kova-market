<?php

namespace App\Models;

use App\Enums\BannerPlacement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Home page banner (F-010). Displayed while visible and within its optional date window.
 */
#[Fillable([
    'placement', 'product_id', 'image', 'subtitle', 'highlight', 'title', 'tagline', 'badge', 'price', 'compare_at_price',
    'url', 'button_label', 'position', 'starts_at', 'ends_at', 'is_visible',
])]
class Banner extends Model
{
    use LogsActivity;

    public const DEFAULT_BUTTON = 'Acheter maintenant';

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
            'placement' => BannerPlacement::class,
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * The product the banner promotes, if any: its price, discount and page are used instead of the typed ones.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[Scope]
    protected function live(Builder $query): Builder
    {
        return $query
            ->where('is_visible', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Shape expected by the home page partials (same keys as config/homepage.php).
     *
     * @return array<string, mixed>
     */
    public function toStorefront(): array
    {
        $banner = [
            ...$this->only(['id', 'image', 'subtitle', 'highlight', 'title', 'tagline', 'badge', 'price', 'compare_at_price', 'url']),
            'placement' => $this->placement->value,
            'button' => filled($this->button_label) ? $this->button_label : self::DEFAULT_BUTTON,
        ];

        // A promoted product on sale gives the current price, the crossed-out one and the discount, and its page
        // unless the banner has its own link. A product taken off the site shows no price at all.
        if ($this->product_id !== null) {
            $product = $this->product;
            $onSite = $product?->is_active ?? false;

            $banner = [
                ...$banner,
                'price' => $onSite ? $product->price : null,
                'compare_at_price' => $onSite && $product->isOnSale() ? $product->compare_at_price : null,
                'badge' => $onSite && $product->isOnSale() ? '-'.$product->discountPercentage().' %' : null,
                'url' => filled($this->url) ? $this->url : ($onSite ? $product->url() : null),
            ];
        }

        return $banner;
    }
}
