<?php

namespace App\Models;

use App\Enums\SaleUnit;
use App\Support\SaleQuantity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order line: name, variant, SKU and price copied at order time (F-054); a pack line also keeps its contents
 * (F-093) as a list of {name, variant, sku, quantity per pack}.
 */
#[Fillable([
    'order_id', 'product_variant_id', 'product_id', 'product_name', 'variant_label', 'bundle_contents', 'sku', 'image',
    'unit_price', 'quantity', 'sale_unit', 'unit_label', 'line_total',
])]
class OrderItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
            'bundle_contents' => 'array',
            'sale_unit' => SaleUnit::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The unit frozen on the line: the product may be sold otherwise later, the order must still read right. */
    public function saleQuantity(): SaleQuantity
    {
        return SaleQuantity::forOrderItem($this);
    }

    /** "1,75 kg", "3 tas", "2". */
    public function quantityLabel(): string
    {
        return $this->saleQuantity()->format($this->quantity);
    }

    /** "1,75 kg × 1 000 FCFA/kg" or "2 × 4 500 FCFA". */
    public function pricing(): string
    {
        return $this->saleQuantity()->describe($this->quantity, $this->unit_price);
    }

    /** Units counted as sold (popularity): a line sold by weight or volume counts as one sale. */
    public function soldUnits(): int
    {
        return $this->saleQuantity()->unit->isMeasured() ? 1 : $this->quantity;
    }

    /**
     * "Contient : 1 × Casque X1 (Couleur : Noir), 2 × Câble USB-C" for a pack line, null otherwise.
     */
    public function contentsSummary(): ?string
    {
        if (empty($this->bundle_contents)) {
            return null;
        }

        return 'Contient : '.collect($this->bundle_contents)
            ->map(fn (array $component) => "{$component['quantity']} × {$component['name']}".($component['variant'] ? " ({$component['variant']})" : ''))
            ->join(', ');
    }
}
