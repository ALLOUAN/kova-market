<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Storefront\Comparison;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Product comparison: the "Comparer" button of product cards and pages, the bar listing the chosen products, and
 * the side-by-side page.
 */
class CompareController extends Controller
{
    public function __construct(private Comparison $comparison) {}

    public function index(): View
    {
        $products = $this->comparison->products();

        return view('pages.compare', [
            'products' => $products,
            'labels' => Comparison::specificationLabels($products),
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $added = $this->comparison->toggle($product);

        $message = match ($added) {
            true => "« {$product->name} » est ajouté au comparateur.",
            false => "« {$product->name} » est retiré du comparateur.",
            null => 'Vous comparez déjà '.Comparison::MAX.' produits : retirez-en un pour en ajouter un autre.',
        };

        return $this->answer($request, $message, $added !== null, ['added' => $added]);
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->comparison->clear();

        return $this->answer($request, 'Le comparateur est vidé.', true);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function answer(Request $request, string $message, bool $ok, array $extra = []): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return back()->with($ok ? 'cart_status' : 'cart_error', $message);
        }

        return response()->json([
            ...$extra,
            'ok' => $ok,
            'message' => $message,
            'count' => $this->comparison->count(),
            'ids' => $this->comparison->ids(),
            'bar' => view('partials.compare-bar', ['compared' => $this->comparison->products()])->render(),
        ], $ok ? 200 : 422);
    }
}
