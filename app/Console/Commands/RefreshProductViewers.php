<?php

namespace App\Console\Commands;

use App\Services\Storefront\ProductViewers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Keeps the "N personnes ont vu ce produit" badge of the product cards in step with the real visits.
 */
#[Signature('catalog:refresh-viewers')]
#[Description('Count the recent visitors of each product page for the product cards badge')]
class RefreshProductViewers extends Command
{
    public function handle(ProductViewers $viewers): int
    {
        $this->info($viewers->refresh().' produit(s) affichent le badge.');

        return self::SUCCESS;
    }
}
