<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Sellable combination of attribute values with its own SKU, prices (whole FCFA) and stock (F-035).
 *
 * The stock column is not fillable: it only changes through App\Services\Catalog\StockManager,
 * which records a stock movement every time.
 */
#[Fillable(['product_id', 'sku', 'price', 'compare_at_price', 'sale_starts_at', 'sale_ends_at', 'low_stock_threshold', 'is_default', 'position'])]
class ProductVariant extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        // The product keeps its storefront summary (total stock, "from" price, variant count) up to date.
        static::saved(fn (ProductVariant $variant) => $variant->product->syncFromVariants());
        static::deleted(fn (ProductVariant $variant) => $variant->product->syncFromVariants());
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
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class)->with('attribute');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    /**
     * "Couleur : Noir, Taille : XL", or "Modèle unique" for a variant without attributes.
     */
    public function label(): string
    {
        $values = $this->attributeValues->sortBy(fn (AttributeValue $value) => $value->attribute->position);

        return $values->isEmpty() ? 'Modèle unique' : $values->map->label()->implode(', ');
    }

    /**
     * Whether a reduced price is set ("price" below "compare_at_price"), whatever its dates.
     */
    public function hasSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    /**
     * Whether the reduced price applies now (F-090): no dates means a sale without limit.
     */
    public function saleIsRunning(): bool
    {
        return $this->hasSale()
            && ($this->sale_starts_at === null || ! $this->sale_starts_at->isFuture())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());
    }

    /**
     * Price the customer pays now: the reduced price during its window, the normal price outside it.
     */
    public function currentPrice(): int
    {
        return $this->hasSale() && ! $this->saleIsRunning() ? $this->compare_at_price : $this->price;
    }

    /**
     * Crossed-out price shown next to the current price, only while the sale runs.
     */
    public function currentComparePrice(): ?int
    {
        return $this->saleIsRunning() ? $this->compare_at_price : null;
    }

    /**
     * Alert threshold in base units, like the stock: the variant's own, else the general one counted in the
     * product's displayed units (5 → 5 pieces, or 5 kg = 5 000 g).
     */
    public function lowStockThreshold(): int
    {
        return $this->low_stock_threshold
            ?? config('storefront.product_card.limited_stock_threshold') * ($this->product?->saleQuantity()->unit->factor() ?? 1);
    }

    /** "5", "2,5 kg": the threshold as the team reads it. */
    public function lowStockThresholdLabel(): string
    {
        return $this->product ? $this->product->saleQuantity()->format($this->lowStockThreshold()) : (string) $this->lowStockThreshold();
    }

    public function isLowOnStock(): bool
    {
        return $this->stock <= $this->lowStockThreshold();
    }
}
