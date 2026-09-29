<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Services\Storefront\ProductListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Product lists of the storefront: whole shop and search results, category pages, brand pages (F-020 to F-024, P-01).
 */
class CatalogController extends Controller
{
    public function __construct(private ProductListing $listing) {}

    public function index(Request $request): View|RedirectResponse
    {
        // The header search can be narrowed to a department: continue on that category page.
        if (filled($slug = $request->query('category')) && $category = Category::firstWhere('slug', $slug)) {
            return redirect()->route('categories.show', ['category' => $category, ...$request->except('category')]);
        }

        $query = trim((string) $request->query('q', ''));

        return view('pages.catalog', [
            ...$this->listing->list($request),
            'title' => $query !== '' ? "Résultats pour « {$query} »" : 'Tous les produits',
            'breadcrumb' => [],
            'category' => null,
            'brand' => null,
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        $category->load('parent.parent', 'children');

        return view('pages.catalog', [
            ...$this->listing->list($request, category: $category),
            'title' => $category->name,
            'breadcrumb' => collect($category->ancestry())->slice(0, -1)->all(),
            'category' => $category,
            'brand' => null,
        ]);
    }

    /**
     * A home page selection ("Offres du jour", "Les meilleures offres du jour"…) in full, with filters and sorting.
     */
    public function collection(Request $request, Collection $collection): View
    {
        abort_unless($collection->hasStarted(), 404);

        return view('pages.catalog', [
            ...$this->listing->list($request, collection: $collection),
            'title' => $collection->name,
            'breadcrumb' => [],
            'category' => null,
            'brand' => null,
        ]);
    }

    public function brand(Request $request, Brand $brand): View
    {
        return view('pages.catalog', [
            ...$this->listing->list($request, brand: $brand),
            'title' => $brand->name,
            'breadcrumb' => [],
            'category' => null,
            'brand' => $brand,
        ]);
    }
}
