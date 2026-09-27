<?php

namespace App\Events;

use App\Models\ProductVariant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A variant's stock just went down to (or under) its alert threshold (F-103, F-135).
 */
class StockLow implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ProductVariant $variant) {}
}
