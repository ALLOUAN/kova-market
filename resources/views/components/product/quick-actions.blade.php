{{-- Quick view + wishlist buttons over the product image. Quick view mode comes from config/storefront.php. --}}
<div class="rbt-quick-btn-grp has-mixup-midlayer bottom-right--position">
    @if (config('storefront.product_card.quick_view') === 'sidenav')
        <button class="rbt-search-btn rbt-quick-btn tooltips rbt-quickview-sidenav-activation" type="button" data-tooltip="Aperçu rapide" data-tooltip-position="left"><i class="fa-regular fa-magnifying-glass-plus"></i></button>
    @else
        <button class="rbt-search-btn rbt-quick-btn tooltips" type="button" data-bs-toggle="modal" data-bs-target="#quickviewModal" data-tooltip="Aperçu rapide" data-tooltip-position="left"><i class="fa-regular fa-magnifying-glass-plus"></i></button>
    @endif
    <button class="rbt-wishlisted-btn rbt-quick-btn tooltips" type="button" data-bs-toggle="modal" data-bs-target="#wishlistModal" data-tooltip="Ajouter aux favoris" data-tooltip-position="left"><i class="fa-regular fa-heart"></i></button>
</div>
