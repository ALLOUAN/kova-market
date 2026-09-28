<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A former slug of a product, category, brand or page (F-150), pointing to the record rather than to its new slug:
 * after several renames every old address still reaches the current one in a single 301.
 */
#[Fillable(['redirectable_type', 'redirectable_id', 'old_slug'])]
class SlugRedirect extends Model
{
    public function redirectable(): MorphTo
    {
        return $this->morphTo();
    }
}
