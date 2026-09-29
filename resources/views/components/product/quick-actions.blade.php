@props(['product'])

{{-- Quick view + wishlist buttons over the product image. Quick view mode comes from config/storefront.php;
     storefront.js loads the product into the modal or side panel. --}}
<div class="rbt-quick-btn-grp has-mixup-midlayer bottom-right--position">
    @if (config('storefront.product_card.quick_view') === 'sidenav')
        <button class="rbt-search-btn rbt-quick-btn tooltips rbt-quickview-sidenav-activation" type="button" data-quick-view-url="{{ route('products.quick-view', $product) }}" data-product-url="{{ $product->url() }}" data-tooltip="Aperçu rapide" data-tooltip-position="left" aria-label="Aperçu rapide de {{ $product->name }}"><i class="fa-regular fa-magnifying-glass-plus"></i></button>
    @else
        <button class="rbt-search-btn rbt-quick-btn tooltips" type="button" data-bs-toggle="modal" data-bs-target="#quickviewModal" data-quick-view-url="{{ route('products.quick-view', $product) }}" data-product-url="{{ $product->url() }}" data-tooltip="Aperçu rapide" data-tooltip-position="left" aria-label="Aperçu rapide de {{ $product->name }}"><i class="fa-regular fa-magnifying-glass-plus"></i></button>
    @endif
    @if (config('storefront.features.wishlist'))
        <x-wishlist-button :product="$product" />
    @endif
    @if (config('storefront.features.compare'))
        <x-compare-button :product="$product" />
    @endif
</div>
