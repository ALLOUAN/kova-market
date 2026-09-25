<x-modal id="addedcartModal" dialog-class="modal-dialog-centered xs-size" content-class="p--24" :folder="false">
    <div class="rbt-wishlist-added-wrapper">
        <div class="rbt-quick-info-tag d-inline-flex w-100">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="13" viewbox="0 0 16 13" fill="none">
                <path d="M3.98047 1.58203C4.36328 0.96224 4.91016 0.643229 5.62109 0.625H10.3516C11.0807 0.643229 11.6458 0.96224 12.0469 1.58203L14.5625 5.73828C14.9453 6.41276 14.9453 7.08724 14.5625 7.76172L12.0469 11.918C11.6458 12.5378 11.0807 12.8568 10.3516 12.875H5.62109C4.91016 12.8568 4.36328 12.5378 3.98047 11.918L1.4375 7.76172C1.05469 7.08724 1.05469 6.41276 1.4375 5.73828L3.98047 1.58203ZM11.1719 5.54688C11.3177 5.38281 11.3906 5.20052 11.3906 5C11.3906 4.79948 11.3177 4.61719 11.1719 4.45312C11.0078 4.30729 10.8255 4.23438 10.625 4.23438C10.4245 4.23438 10.2422 4.30729 10.0781 4.45312L7.125 7.40625L5.92188 6.20312C5.75781 6.05729 5.57552 5.98438 5.375 5.98438C5.17448 5.98438 4.99219 6.05729 4.82812 6.20312C4.68229 6.36719 4.60938 6.54948 4.60938 6.75C4.60938 6.95052 4.68229 7.13281 4.82812 7.29688L6.57812 9.04688C6.74219 9.19271 6.92448 9.26562 7.125 9.26562C7.32552 9.26562 7.50781 9.19271 7.67188 9.04688L11.1719 5.54688Z" fill="#24BD25"></path>
            </svg>
            <p><strong>Product has been sucessfully added</strong></p>
        </div>
        <div class="row row--16">
            <div class="col-md-6 col-12 mt--16">
                <div class="rbt-card rbt-product-card">
                    <div class="rbt-card-img rbt-rounded--8">
                        <a href="#">
                            <img src="{{ asset('assets/images/product-img/cart-product/cart-product-01-lg.webp') }}" alt="eCommerce Product Image">
                        </a>
                    </div>
                    <div class="rbt-card-body">
                        <p class="rbt-title mb--0 b1"><a href="#">Harman Kardon Aura
                                Studio 3 Bluetooth Speaker</a>
                    </p></div>
                    <div class="pricing-part mt--0">
                        <span class="price-text">$179.98</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12 mt--16">
                <div class="rbt-cart-info text-center">
                    <p class="cart-quantity-text b1 rbt-text-color-heading mb--0">There are <span class="number">2</span> items in your cart</p>
                    <div class="pricing-part justify-content-center align-items-center">
                        <p class="b3 rbt-text-color-heading rbt-text-medium mb--0">Total: </p>
                        <span class="price-text rbt-text-color-primary h4 mb--0">$179.98</span>
                    </div>
                    <a class="rbt-btn rbt-btn-sm d-block mt--16" href="#">Checkout</a>
                    <a class="rbt-btn rbt-btn-sm d-block rbt-btn-naked b3 text-decoration-underline mt--8 rbt-text-semi-bold" href="#">View My Cart</a>
                    <a class="rbt-btn rbt-btn-sm d-block rbt-btn-naked b3 text-decoration-underline mt--8 rbt-text-semi-bold" href="#">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
    <hr class="rbt-separator rbt-separator-gray200 mt--16 mb--16">
    <div class="rbt-rec-prd-section">
        <div class="rbt-title h6">Recommended Products</div>
        <div class="swiper rbt-rec-prd-swiper rbt-swiper-scrollbar-bottom">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="rbt-card rbt-product-card rbt-list-view-variation list-view-md">
                        <div class="inner">
                            <div class="rbt-card-img rbt-bg-color-default">
                                <a href="#"><img src="{{ asset('assets/images/product-img/electronics/electronics-bg-trans-01.webp') }}" alt="Card Image"></a>
                            </div>
                            <div class="rbt-card-body p-0">
                                <a href="#" class="rbt-card-subtitle rbt-card-catagories-text">Headphones</a>
                                <p class="rbt-card-title mt--0 b4"><a href="#">Samsung
                                        Galaxy Buds 2 Pro Earbuds</a>
                                </p>
                                <div class="pricing-part mt--0">
                                    <span class="price-text">$428.75</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="rbt-card rbt-product-card rbt-list-view-variation list-view-md">
                        <div class="inner">
                            <div class="rbt-card-img rbt-bg-color-default">
                                <a href="#"><img src="{{ asset('assets/images/product-img/electronics/electronics-bg-trans-02.webp') }}" alt="Card Image"></a>
                            </div>
                            <div class="rbt-card-body p-0">
                                <a href="#" class="rbt-card-subtitle rbt-card-catagories-text">Coffee
                                    Machine</a>
                                <p class="rbt-card-title mt--0 b4"><a href="#">Nespresso Vertuo Plus Coffee
                                        Machine</a></p>
                                <div class="pricing-part mt--0">
                                    <span class="price-text">$228.98</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="rbt-card rbt-product-card rbt-list-view-variation list-view-md">
                        <div class="inner">
                            <div class="rbt-card-img rbt-bg-color-default">
                                <a href="#"><img src="{{ asset('assets/images/product-img/electronics/electronics-bg-trans-03.webp') }}" alt="Card Image"></a>
                            </div>
                            <div class="rbt-card-body p-0">
                                <a href="#" class="rbt-card-subtitle rbt-card-catagories-text">Smartwatch</a>
                                <p class="rbt-card-title mt--0 b4"><a href="#">Fitbit
                                        Sense 2 Advanced Smartwatch</a></p>
                                <div class="pricing-part mt--0">
                                    <span class="price-text">$128.90</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rbt-swiper-scrollbar swiper-scrollbar"></div>
        </div>
    </div>
</x-modal>
