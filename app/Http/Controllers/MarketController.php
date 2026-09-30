<?php

namespace App\Http\Controllers;

use App\Services\Storefront\Market;
use Illuminate\View\View;

/**
 * The "Mon Marché" showcase: the market department's categories and a row of products for each.
 */
class MarketController extends Controller
{
    public function __invoke(Market $market): View
    {
        abort_unless($market->enabled(), 404);

        return view('pages.market', [
            'market' => $market,
            'category' => $market->category()->load('children'),
            'rows' => $market->rows(),
            'weighedCount' => $market->weighedProductsCount(),
        ]);
    }
}
