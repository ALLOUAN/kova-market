@props(['product'])

{{-- "Add to cart" (or "Notify me" when sold out, "Choose" when several variants) and "Add to compare" buttons. --}}
<div class="prd-btn-grp">
    @if ($product->isSoldOut())
        <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon" href="#!" data-bs-toggle="modal" data-bs-target="#notifyModal" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}"><i class="fa-regular fa-bell"></i> Me prévenir</a>
    @elseif ($product->variants_count > 1)
        <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon" href="{{ $product->url() }}"><i class="fa-regular fa-sliders"></i> Choisir une option</a>
    @else
        {{-- The single (default) variant goes to the cart; storefront.product_card.cart_action decides what opens next. --}}
        <form method="POST" action="{{ route('cart.items.store') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="open" value="{{ config('storefront.product_card.cart_action') }}">
            <button class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon w-100" type="submit"><i class="fa-regular fa-cart-shopping"></i> Ajouter au panier</button>
        </form>
    @endif
    <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block rbt-btn-transparent has-left-icon rbt-compare-btn-activation rbt-compare-bottom-sidenav-activation" href="#"><i class="fa-regular fa-file-plus-minus"></i>Ajouter au comparateur</a>
</div>
