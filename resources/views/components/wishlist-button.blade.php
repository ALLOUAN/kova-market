@props(['product', 'variant' => 'card'])

{{-- Heart of a product (card or product page): adds to or removes from "Mes favoris". A real form, so it works
     without JavaScript; with it, public/assets/js/wishlist.js answers in place and updates the header counts. --}}
@php($in = app(\App\Services\Storefront\Wishlist::class)->has($product))
<form method="POST" action="{{ route('wishlist.toggle', $product) }}" class="d-inline-block m-0" data-wishlist-form>
    @csrf
    @if ($variant === 'card')
        {{-- Not the theme's "rbt-wishlisted-btn": its script sends that button to a template page. --}}
        <button type="submit" @class(['kova-wishlist-btn rbt-quick-btn tooltips', 'is-wishlisted' => $in]) data-tooltip="{{ $in ? 'Retirer des favoris' : 'Ajouter aux favoris' }}" data-tooltip-position="left"
            aria-pressed="{{ $in ? 'true' : 'false' }}" aria-label="{{ $in ? 'Retirer' : 'Ajouter' }} « {{ $product->name }} » {{ $in ? 'des' : 'aux' }} favoris">
            <i @class(['fa-heart', 'fa-solid' => $in, 'fa-regular' => ! $in]) aria-hidden="true"></i>
        </button>
    @else
        <button type="submit" @class(['rbt-btn rbt-btn-border kova-wishlist-page-btn', 'is-wishlisted' => $in]) aria-pressed="{{ $in ? 'true' : 'false' }}">
            <i @class(['fa-heart me-2', 'fa-solid' => $in, 'fa-regular' => ! $in]) aria-hidden="true"></i><span data-wishlist-label>{{ $in ? 'Dans vos favoris' : 'Ajouter aux favoris' }}</span>
        </button>
    @endif
</form>
