<div class="rbt-header-common-sticky-activation rbt-header-wrapper-common justify-content-between rbt-bg-color-white">
    <div class="rbt-header-campaign rbt-header-campaign-1 rbt-header-top-news rbt-topbar-bg-img rbt-topbar-bg-one w-100">
        <div class="rbt-corner-portion-wrapper">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="inner justify-content-center">
                            <div class="rbt-text-swiper-container rbt-arrow-vertical">
                                <div class="swiper-wrapper">
                                    @foreach (config('storefront.announcements.campaign') as $message)
                                        <div class="swiper-slide">
                                            <div class="rbt-fancy-item fancy-menu-text fancy-menu-center">
                                                <p class="rbt-fancy-text rbt-text-color-white">{{ $message }}
                                                    <a class="rbt-text-color-white" href="#">Shop Now</a>
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="rbt-verticle-arrow rbt-text-color-white rbt-arrow-prev">
                                    <i class="fa-regular fa-chevron-up"></i>
                                </div>
                                <div class="rbt-verticle-arrow rbt-text-color-white rbt-arrow-next">
                                    <i class="fa-regular fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="icon-close position-right">
            <button class="rbt-round-btn btn-white-off bgsection-activation" aria-label="Close Button">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    <div class="container">
        <div class="mainbar-row rbt-mainbar-row-md-height  align-items-center">
            <div class="header-left">
                <div class="rbt-header-content d-flex">
                    <div class="header-info p-0 d-none d-xxl-flex mr--24">
                        @include('partials.header.burger')
                    </div>
                    <div class="header-info d-xl-block d-none">
                        @include('partials.header.logo', ['class' => 'rbt-logo-height-sm'])
                    </div>
                </div>
                @include('partials.header.mobile-menu-button')
            </div>

            <div class="header-info d-xl-none d-block">
                @include('partials.header.logo')
            </div>

            <div class="rbt-header-content d-none d-xl-block">
                <div class="header-info">
                    @include('partials.header.main-menu', ['idSuffix' => '-cs', 'headingTag' => 'p'])
                </div>
            </div>

            <div class="header-right">
                <ul class="rbt-quick-access rbt-gap--12">
                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 tooltips tooltip-distance-lg" data-tooltip="Search" data-tooltip-position="bottom">
                        <a class="rbt-round-btn has-rbt-md-fsize rbt-common-search-trigger-active rbt-modern-close-btn" href="#" aria-label="Search">
                            <i class="fa-regular fa-search search-icon"></i>
                            <div class="modern-close-wrapper"></div>
                        </a>
                    </li>

                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 d-none d-lg-flex tooltips tooltip-distance-lg" data-tooltip="Sign In" data-tooltip-position="bottom">
                        <a class="rbt-round-btn has-rbt-md-fsize" href="#!" data-bs-toggle="modal" data-bs-target="#signinModal" aria-label="Sign In">
                            <i class="fa-regular fa-user"></i>
                        </a>
                    </li>

                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-4 tooltips tooltip-distance-lg  d-none d-lg-flex" data-tooltip="Compare" data-tooltip-position="bottom">
                        <a class="rbt-round-btn has-rbt-md-fsize" href="#" data-bs-toggle="modal" data-bs-target="#compareviewModal" aria-label="Compare">
                            <i class="fa-regular fa-code-compare"></i>
                            <div class="access-box-count">6</div>
                        </a>
                    </li>

                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-5 rbt-wishlist d-none d-lg-flex tooltips tooltip-distance-lg" data-tooltip="Wishlist" data-tooltip-position="bottom">
                        <a class="rbt-round-btn has-rbt-md-fsize" href="#!" data-bs-toggle="modal" data-bs-target="#wishlistModal" aria-label="Wishlist">
                            <i class="fa-regular fa-heart"></i>
                            <div class="access-box-count">7</div>
                        </a>
                    </li>

                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-5 rbt-access-box-has-bg-hover rbt-mini-cart tooltips tooltip-distance-lg" data-tooltip="Cart" data-tooltip-position="bottom">
                        <a class="rbt-cart-sidenav-activation" href="#!" aria-label="Cart">
                            <span class="rbt-round-btn has-rbt-md-fsize">
                                <i class="fa-regular fa-bag-shopping"></i>
                                <span class="access-box-count rbt-shiny">3</span>
                            </span>
                            <div class="content ml--4">
                                <span class="title-text">$2390</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @include('partials.header.search-dropdown')
</div>
