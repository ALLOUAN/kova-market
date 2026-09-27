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
#[Fillable(['product_id', 'sku', 'price', 'compare_at_price', 'low_stock_threshold', 'is_default', 'position'])]
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

    public function lowStockThreshold(): int
    {
        return $this->low_stock_threshold ?? config('storefront.product_card.limited_stock_threshold');
    }

    public function isLowOnStock(): bool
    {
        return $this->stock <= $this->lowStockThreshold();
    }
}
