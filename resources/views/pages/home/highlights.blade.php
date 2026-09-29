@php($banner = $banners['highlights'])
<div id="rbt-product-block-03" class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-gray-light">
    <div class="container">
        <div class="row row--12 mt_dec--24">
            <div class="col-xl-6 col-lg-12 col-md-12 col-12 mt--24">
                <div class="rbt-fshape-box-outline-style rbt-fshape-box-outline-style-bg-white rbt-fshape-box-outline-style-sm-size">
                    <div class="row">
                        <div class="col-lg-12">
                            <x-section-title fill="white" heading-class="" small>{{ $highlights->name }}</x-section-title>
                        </div>
                    </div>
                    <div class="rbt-fshape-box">
                        <div class="row row--12 mt_dec--24 rbt-card-row-has-top-separator rbt-two-align-card-row">
                            @foreach ($highlights->products as $product)
                                <div class="col-lg-6 col-md-6 col-sm-6 col-12 mt--24">
                                    <x-product.list-card :product="$product" :order="$loop->iteration" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-12 col-md-12 col-12 mt--24 pt--44 pt_sm--0 pt_lg--0 pt_md--0">
                <div class="rbt-product-banner rbt-product-banner-style-two rbt-curved-style-box h-100">
                    <div class="rbt-banner-inner h-100">
                        <div class="rbt-product-banner-img rbt-full-width-img rbt-scroll-trigger zoom_in animation-order-1">
                            <img src="{{ asset($banner['image']) }}" alt="{{ $banner['highlight'] }}" loading="lazy" decoding="async">
                        </div>
                        <div class="rbt-product-banner-content">
                            <div class="rbt-content-section rbt-scroll-trigger fade_in animation-order-1">
                                <p class="rbt-banner-subtitle mb-0">{{ $banner['subtitle'] }}</p>
                                <h2 class="rbt-banner-title title-capitalize-text mb-0"><span class="rbt-bold--text">{{ $banner['highlight'] }}
                                    </span>{{ $banner['title'] }}</h2>
                                <h3 class="rbt-secondery-subtitle mb-0">{{ $banner['tagline'] }}</h3>
                            </div>
                            <div class="rbt-banner-btn rbt-scroll-trigger fade_in animation-order-2">
                                <a class="rbt-btn rbt-btn-round rbt-magnetic-button" href="{{ $banner['url'] ?? '#' }}"><i class="fa-solid fa-arrow-up-right"></i> ACHETER <br> MAINTENANT</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
