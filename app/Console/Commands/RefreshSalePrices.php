<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dated sale prices (F-090): the cart and the checkout always compute the current price, but the price kept on
 * the product (lists, sorting, price filters, cards) is refreshed here when a sale window opens or closes.
 */
#[Signature('catalog:refresh-sale-prices')]
#[Description('Refresh the product prices whose sale window opened or closed recently')]
class RefreshSalePrices extends Command
{
    public function handle(): int
    {
        $since = now()->subDay();
        $count = 0;

        Product::query()
            ->whereHas('variants', fn (Builder $query) => $query
                ->whereBetween('sale_starts_at', [$since, now()])
                ->orWhereBetween('sale_ends_at', [$since, now()]))
            ->each(function (Product $product) use (&$count): void {
                $product->syncFromVariants();
                $count++;
            });

        $this->info("{$count} produit(s) mis à jour.");

        return self::SUCCESS;
    }
}
