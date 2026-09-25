<x-modal id="wishlistModal" dialog-class="sm-size modal-dialog-centered" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-bg-color-white rbt-content-trs-portion">
            <div class="rbt-wishlist-modal-content">
                <div class="rbt-title rbt-text-bold h5" id="wishlistModalLabel">Product Wishlist</div>
                <div class="rbt-transparent-table-one-wrapper rbt-has-bg-gray pt--0 pb--0 mb--16">
                    <table class="rbt-transparent-table-one mb--0 rbt-wishlist-table">
                        <tbody>

                            <tr>
                                <td class="rbt-product-remove-btn-wrapper">
                                    <button class="rbt-product-remove-btn rbt-round-btn">
                                        <span><i class="fa-solid fa-xmark"></i></span>
                                    </button>
                                </td>
                                <td class="product-thumbnail">
                                    <a href="#">
                                        <img src="{{ asset('assets/images/wishlist/wishlist-prd-1.webp') }}" alt="Product image">
                                    </a>
                                </td>
                                <td class="rbt-wish-product-info">
                                    <div class="rbt-wish-product-name h6">
                                        <a href="#">
                                            JBL PartyBox 100W Speaker
                                        </a>
                                    </div>
                                    <div class="rbt-product-price-text rbt-text-color-primary">
                                        <span>$159.00</span>
                                    </div>
                                    <span class="rbt-product-id"><span class="rbt-text-semi-bold">SKU:</span>
                                        #180036458</span>
                                </td>

                                <td>
                                    <div class="rbt-button-group">
                                        <a class="rbt-btn rbt-btn-sm has-left-icon" href="#">
                                            <i class="fa-regular fa-cart-shopping"></i>
                                            Add To Cart
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="rbt-product-remove-btn-wrapper">
                                    <button class="rbt-product-remove-btn rbt-round-btn">
                                        <span><i class="fa-solid fa-xmark"></i></span>
                                    </button>
                                </td>
                                <td class="product-thumbnail">
                                    <a href="#">
                                        <img src="{{ asset('assets/images/wishlist/wishlist-prd-2.webp') }}" alt="Product image">
                                    </a>
                                </td>
                                <td class="rbt-wish-product-info">
                                    <div class="rbt-wish-product-name h6">
                                        <a href="#">
                                            Fossil Gen 6 Hybrid Smartwatch
                                        </a>
                                    </div>
                                    <div class="rbt-product-price-text rbt-text-color-primary">
                                        <span>$209.00</span>
                                    </div>
                                    <span class="rbt-product-id"><span class="rbt-text-semi-bold">SKU:</span>
                                        #180036565</span>
                                </td>

                                <td>
                                    <div class="rbt-button-group">
                                        <a class="rbt-btn rbt-btn-sm has-left-icon" href="#">
                                            <i class="fa-regular fa-cart-shopping"></i>
                                            Add To Cart
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
                <div class="rbt-wishlist-modal-footer d-flex flex-wrap rbt-gap--16 justify-content-between align-items-center">
                    <a href="#" class="rbt-link"><span class="icon mr--4"><i class="fa-sharp fa-regular fa-heart"></i></span>Open wishlist page</a>
                    <a href="#" class="rbt-link">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</x-modal>
