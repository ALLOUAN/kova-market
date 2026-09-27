<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One component of a pack (F-093): a variant of another product and how many of it the pack holds.
 */
#[Fillable(['bundle_id', 'product_variant_id', 'quantity', 'position'])]
class BundleItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Whole packs this component allows: its stock divided by the quantity per pack (none while its product
     * is off sale).
     */
    public function packsAvailable(): int
    {
        return $this->variant->product?->is_active ? intdiv($this->variant->stock, max(1, $this->quantity)) : 0;
    }
}
