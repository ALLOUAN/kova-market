@props(['product', 'variant' => 'card'])

{{-- "Comparer" of a product (card or product page). A real form, so it works without JavaScript; with it,
     public/assets/js/compare.js answers in place and refreshes the comparison bar. Not the theme's "rbt-compare-btn"
     classes: its script sends those buttons to a template page. --}}
@php($in = app(\App\Services\Storefront\Comparison::class)->has($product))
<form method="POST" action="{{ route('compare.toggle', $product) }}" class="d-inline-block m-0" data-compare-form data-product-id="{{ $product->id }}">
    @csrf
    @if ($variant === 'card')
        <button type="submit" @class(['kova-compare-btn rbt-quick-btn tooltips', 'is-compared' => $in]) data-tooltip="{{ $in ? 'Retirer du comparateur' : 'Comparer' }}" data-tooltip-position="left"
            aria-pressed="{{ $in ? 'true' : 'false' }}" aria-label="Comparer « {{ $product->name }} »">
            <i class="fa-regular fa-code-compare" aria-hidden="true"></i>
        </button>
    @else
        <button type="submit" @class(['rbt-btn rbt-btn-border kova-compare-page-btn', 'is-compared' => $in]) aria-pressed="{{ $in ? 'true' : 'false' }}">
            <i class="fa-regular fa-code-compare me-2" aria-hidden="true"></i><span data-compare-label>{{ $in ? 'Dans le comparateur' : 'Comparer' }}</span>
        </button>
    @endif
</form>
