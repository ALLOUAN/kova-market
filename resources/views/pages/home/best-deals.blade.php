@php($banner = $banners['best_deals'])
<div id="rbt-product-block-02" class="rbt-component-area rbt-catagories-area pt_lg--100 rbt-section-gap2 rbt-bg-color-white">
    <div class="container">
        <div class="row row--12">
            <div class="col-lg-12 col-md-12 col-sm-12 col-12 mt--32 mt_sm--0">
                <div class="rbt-product-banner rbt-product-banner-style-one">
                    <div class="rbt-product-banner-img rbt-scroll-trigger zoom_in animation-order-1">
                        <img src="{{ asset($banner['image']) }}" alt="{{ $banner['highlight'] }}">
                    </div>
                    <div class="rbt-banner-inner rbt-curved-style-box">
                        <div class="rbt-product-banner-content">
                            <div class="rbt-content-section rbt-scroll-trigger fade_in animation-order-1">
                                <p class="rbt-banner-subtitle mb-0">{{ $banner['subtitle'] }}</p>
                                <h2 class="rbt-banner-title title-capitalize-text mb-0"><span class="rbt-bold--text">{{ $banner['highlight'] }}</span> {{ $banner['title'] }} </h2>
                                <h3 class="rbt-secondery-subtitle mb-0">{{ $banner['tagline'] }}</h3>
                            </div>
                            <div class="rbt-banner-btn rbt-magnet-area rbt-banner-btn rbt-scroll-trigger fade_in animation-order-2">
                                <a class="rbt-btn rbt-btn-round rbt-magnetic-button" href="#"><i class="fa-solid fa-arrow-up-right"></i> SHOP <br> NOW</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rbt-fshape-box-outline-style rbt-fshape-box-outline-style-extend-width rbt-product-fshape-box-outline-style">
            <div class="row rbt-section-gap2Top pt_sm--0 pt_md--80 mt--16">
                <div class="col-lg-12">
                    <x-section-title class="rbt-border-color-primary rbt-bg-color-gray-light" accent="primary">{{ $bestDeals->name }}</x-section-title>
                    @if ($bestDeals->ends_at?->isFuture())
                        <div class="rbt-offer-countdown-section rbt-offer-countdown-section-primary">
                            <h2 class="rbt-sm-title h6">Hurry up! Offer ends in</h2>
                            <div class="rbt-countdown-section d-flex justify-content-center align-items-center">
                                <div class="rbt-countdown-one bg-variation-black">
                                    <x-countdown :date="$bestDeals->ends_at" />
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="rbt-fshape-box rbt-bg-color-gray-light rbt-border-color-primary">
                <div class="row row--12 mt_dec--24 rbt-mobile-row">
                    @foreach ($bestDeals->products as $product)
                        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-6 mt--24">
                            <x-product.card :product="$product" :order="$loop->iteration" heading="h2" progress />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
