<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Storefront\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Mes favoris": the heart of product cards and pages, and the page listing the favourites (visitors included).
 */
class WishlistController extends Controller
{
    public function __construct(private Wishlist $wishlist) {}

    public function index(): View
    {
        return view('pages.wishlist', ['products' => $this->wishlist->products()]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $added = $this->wishlist->toggle($product);
        $message = $added ? "« {$product->name} » est dans vos favoris." : "« {$product->name} » est retiré de vos favoris.";

        return $request->expectsJson()
            ? response()->json(['added' => $added, 'count' => $this->wishlist->count(), 'message' => $message])
            : back()->with('cart_status', $message);
    }
}
