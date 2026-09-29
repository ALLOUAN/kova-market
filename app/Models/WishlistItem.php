<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A favourite product of a customer or of a visitor (see App\Services\Storefront\Wishlist).
 */
#[Fillable(['user_id', 'visitor', 'product_id'])]
class WishlistItem extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
