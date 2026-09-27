<?php

namespace App\Models;

use App\Enums\StockMovementReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stock change of a variant: signed quantity, resulting stock, reason and author (F-103).
 */
#[Fillable(['product_variant_id', 'quantity', 'stock_after', 'reason', 'note', 'user_id'])]
class StockMovement extends Model
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
            'stock_after' => 'integer',
            'reason' => StockMovementReason::class,
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
