<?php

namespace App\Models;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Support\Money;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Promo code (F-091): fixed amount, percentage or free delivery, with dates, caps, a minimum order and
 * optional target categories or products. Codes are stored upper case and typed in any case.
 */
#[Fillable([
    'code', 'description', 'type', 'value', 'minimum_subtotal', 'starts_at', 'ends_at',
    'usage_limit', 'usage_limit_per_customer', 'target', 'is_active', 'is_public',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory, LogsActivity;

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
            'type' => CouponType::class,
            'target' => CouponTarget::class,
            'value' => 'integer',
            'minimum_subtotal' => 'integer',
            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'times_used' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    protected function code(): Attribute
    {
        return Attribute::set(fn (string $value) => self::normalize($value));
    }

    public static function normalize(string $code): string
    {
        return Str::upper(trim($code));
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', self::normalize($code))->first();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Codes shown to customers in the "Codes promo" window: public, on, in their dates and not used up.
     */
    #[Scope]
    protected function listed(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('is_public', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn (Builder $query) => $query->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'))
            ->orderBy('ends_at')
            ->orderBy('id');
    }

    /**
     * Whether the code applies to this product (a category target also covers its sub-categories).
     */
    public function covers(Product $product): bool
    {
        return match ($this->target) {
            CouponTarget::All => true,
            CouponTarget::Products => $this->products->contains($product->getKey()),
            CouponTarget::Categories => $this->categories->pluck('id')
                ->intersect(array_filter([$product->category_id, $product->category?->parent_id]))
                ->isNotEmpty(),
        };
    }

    /**
     * Short customer-facing label, e.g. "-10 %", "-5 000 FCFA", "Livraison offerte".
     */
    public function benefitLabel(): string
    {
        return match ($this->type) {
            CouponType::Fixed => '-'.Money::format($this->value),
            CouponType::Percentage => "-{$this->value}\u{00A0}%",
            CouponType::FreeShipping => 'Livraison offerte',
        };
    }
}
