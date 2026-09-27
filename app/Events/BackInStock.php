<?php

namespace App\Events;

use App\Models\ProductVariant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A sold-out variant has stock again (restocking, return, cancelled order) (EX-17).
 */
class BackInStock implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ProductVariant $variant) {}
}
