@props(['product', 'compact' => false])

{{-- "Add to cart" on every card: straight to the cart for a single-variant product, through the quick view (choose
     the option, then add) when there are several; "Notify me" when sold out. Comparing is the quick button over
     the image (x-compare-button). "compact" draws a small pill for the list cards. --}}
@php
    $variantsCount = $product->variants_count ?? $product->variants()->count();
    $buttonClass = $compact
        ? 'kova-card-cart-btn'
        : 'rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon w-100';
@endphp
<div @class(['prd-btn-grp', 'kova-card-cart' => $compact])>
    @if ($product->isSoldOut())
        <a class="{{ $compact ? 'kova-card-cart-btn kova-card-cart-btn--quiet' : 'rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon' }}" href="#!" data-bs-toggle="modal" data-bs-target="#notifyModal" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}"><i class="fa-regular fa-bell"></i> Me prévenir</a>
    @elseif ($variantsCount > 1)
        <button class="{{ $buttonClass }}" type="button" data-bs-toggle="modal" data-bs-target="#quickviewModal" data-quick-view-url="{{ route('products.quick-view', $product) }}" data-product-url="{{ $product->url() }}" aria-label="Ajouter « {{ $product->name }} » au panier (choisir une option)">
            <i class="fa-regular fa-cart-shopping"></i> {{ $compact ? 'Ajouter' : 'Ajouter au panier' }}
        </button>
    @else
        {{-- The single (default) variant goes to the cart; storefront.product_card.cart_action decides what opens next. --}}
        <form method="POST" action="{{ route('cart.items.store') }}" data-cart-form>
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="open" value="{{ config('storefront.product_card.cart_action') }}">
            <button class="{{ $buttonClass }}" type="submit" aria-label="Ajouter « {{ $product->name }} » au panier"><i class="fa-regular fa-cart-shopping"></i> {{ $compact ? 'Ajouter' : 'Ajouter au panier' }}</button>
        </form>
    @endif
</div>
