<?php

namespace App\Services\Catalog;

use App\Models\ProductVariant;
use RuntimeException;

/**
 * Thrown when a stock change would leave a variant below zero.
 */
class InsufficientStock extends RuntimeException
{
    public function __construct(public readonly ProductVariant $variant, public readonly int $requested)
    {
        parent::__construct("Stock insuffisant pour {$variant->sku} : {$variant->stock} disponible(s), {$requested} demandé(s).");
    }
}
