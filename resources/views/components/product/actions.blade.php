@props(['product'])

{{-- "Add to cart" (or "Notify me" when sold out) and "Add to compare" buttons. --}}
<div class="prd-btn-grp">
    @if ($product->isSoldOut())
        <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon" href="#!" data-bs-toggle="modal" data-bs-target="#notifyModal"><i class="fa-regular fa-bell"></i> Me prévenir</a>
    @elseif (config('storefront.product_card.cart_action') === 'popup')
        <button class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon" type="button" data-bs-toggle="modal" data-bs-target="#popup-cartModal"><i class="fa-regular fa-cart-shopping"></i> Ajouter au panier</button>
    @else
        <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block has-left-icon rbt-cart-sidenav-activation" href="#"><i class="fa-regular fa-cart-shopping"></i> Ajouter au panier</a>
    @endif
    <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn d-block rbt-btn-transparent has-left-icon rbt-compare-btn-activation rbt-compare-bottom-sidenav-activation" href="#"><i class="fa-regular fa-file-plus-minus"></i>Ajouter au comparateur</a>
</div>
