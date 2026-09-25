<x-modal id="quickviewEditCartModal" dialog-class="modal-dialog-centered rbt-cart-edit-area" labelled>
    <div class="rbt-top-folder-shape-wrapper">

        <div class="rbt-single-product-area rbt-bg-color-white rbt-content-trs-portion">

            <div class="rbt-title rbt-modal-title mb--16 h6">Edit Option For You</div>
            <div class="row row--8 mt_dec--12">
                <div class="col-md-6 col-12 mt--12">
                    <div class="rbt-cart-product-edit-area">
                        <a href="#" class="rbt-cart-product-thumb">
                            <img src="{{ asset('assets/images/product-single/earphone/earphone-05.webp') }}" alt="Product Thumbnail">
                        </a>
                        <div class="rbt-product-info">
                            <p class="rbt-card-title h6" id="quickviewEditCartModalLabel"><a href="#">2021
                                    Apple 12.9-inch iPad Pro Wi-Fi 512GB Gray Space</a></p>
                            <div class="pricing-part mb--12 mt--0">
                                <del class="price-text">$295.00</del>
                                <span class="price-text">$179.98</span>
                            </div>
                            <div class="rbt-qty-area rbt-qty-sm">
                                <button class="qty-item-btn qty-item-btn-decr"><i class="fa-solid fa-minus"></i></button>
                                <input type="number" class="items-qty-input" value="05" min="01">
                                <button class="qty-item-btn qty-item-btn-incr"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12 mt--12 pl--32">
                    <div class="rbt-single-product-content">

                        <div class="rbt-info-wrapper d-flex mt--0">
                            <div class="prd-info-section">
                                <div class="prd-id-text">
                                    <p class="text-bold">Color:</p>
                                    <div class="rbt-color-select-area">
                                        <ul class="rbt-switcher-color-list rbt-switcher-color-list-lg product-switcher-activation">
                                            <li><a class="rbt-switcher--color tooltips rbt-switcher--color-one" data-switcher-color="#2B2B2B" data-src="{{ asset('assets/images/product-single/earphone/earphone-05.webp') }}" data-tooltip="Black" data-tooltip-position="top" href="#">
                                                    <div class="rbt-color-circle"></div>
                                                </a></li>
                                            <li class="active"><a class="rbt-switcher--color tooltips rbt-switcher--color-two" data-switcher-color="#cc999d" data-src="{{ asset('assets/images/product-single/earphone/earphone-02.webp') }}" data-tooltip="Pink" data-tooltip-position="top" href="#">
                                                    <div class="rbt-color-circle"></div>
                                                </a></li>
                                            <li><a class="rbt-switcher--color tooltips rbt-switcher--color-three" data-switcher-color="#9C9B9E" data-src="{{ asset('assets/images/product-single/earphone/earphone-04.webp') }}" data-tooltip="Dark" data-tooltip-position="top" href="#">
                                                    <div class="rbt-color-circle"></div>
                                                </a></li>
                                            <li><a class="rbt-switcher--color tooltips rbt-switcher--color-four" data-switcher-color="#F2EDE7" data-src="{{ asset('assets/images/product-single/earphone/earphone-03.webp') }}" data-tooltip="White" data-tooltip-position="top" href="#">
                                                    <div class="rbt-color-circle"></div>
                                                </a></li>
                                            <li><a class="rbt-switcher--color tooltips rbt-switcher--color-five rbt-switcher--disable disabled" data-switcher-color="#a09fa4" data-src="{{ asset('assets/images/product-single/earphone/earphone-03.webp') }}" data-tooltip="White" data-tooltip-position="top" href="#">
                                                    <div class="rbt-color-circle"></div>
                                                </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rbt-info-wrapper d-flex justify-content-between mt--12">
                            <div class="product-styles-grp d-flex mt--0">
                                <p class="text-bold title">Size :</p>
                                <div class="single-prd-select-area rbt-bg-color-brand-50 rbt-radius">
                                    <div class="rbt-modern-select single-prd-select rbt-sm-size">
                                        <select class="rbt-select-activation">
                                            <option>Extra Large</option>
                                            <option>Large</option>
                                            <option>Medium</option>
                                            <option>Small</option>
                                            <option>Extra Small</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rbt-info-wrapper d-flex mt--12">
                            <div class="product-styles-grp d-flex mt--0">
                                <p class="text-bold title">Style :</p>
                                <div class="content d-flex flex-wrap">
                                    <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn active" href="#">Headphones Only</a>
                                    <a class="rbt-btn rbt-btn-border rbt-btn-sm disabled" href="#">Headphones +
                                        Charging Stand</a>
                                    <a class="rbt-btn rbt-btn-border rbt-btn-sm rbt-square-btn" href="#">Charging Stand</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-12">
                    <a class="rbt-btn d-block text-center rbt-btn-sm rbt-square-btn has-left-icon mt--24 mt_sm--16" href="#">
                        <i class="fa-regular fa-cart-shopping"></i>
                        Update Cart
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-modal>
