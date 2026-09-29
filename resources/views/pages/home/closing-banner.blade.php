@php($banner = $banners['closing'])
<div class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-white">
    <div class="container">
        <div class="row row--12 mt_dec--24">
            <div class="col-lg-12 col-md-12 col-sm-12 col-12 mt--24">
                <div class="rbt-product-banner rbt-product-banner-style-three rbt-curved-style-box">
                    <div class="rbt-banner-inner">
                        <div class="rbt-product-banner-content">
                            <div class="rbt-content-section">
                                <p class="rbt-banner-subtitle mb-0 rbt-scroll-trigger fade_in animation-order-0">{{ $banner['subtitle'] }}</p>
                                <h2 class="rbt-banner-title title-capitalize-text mb-0 text-fsize-38 rbt-scroll-trigger fade_in animation-order-2">
                                    {{ $banner['title'] }} <span class="rbt-bold--text">{{ $banner['highlight'] }}</span></h2>
                                <h3 class="rbt-secondery-subtitle mb-0 rbt-scroll-trigger fade_in animation-order-3">{{ $banner['tagline'] }}</h3>
                                @if (filled($banner['price']))
                                <div class="rbt-pricing-part rbt-scroll-trigger fade_in animation-order-4">
                                    @if (filled($banner['compare_at_price']))
                                    <del class="rbt-dis-price-text">@money($banner['compare_at_price'])</del>
                                    @endif
                                    <span class="rbt-price-text offer-price">@money($banner['price'])</span>
                                </div>
                                @endif
                            </div>
                            <div class="rbt-banner-btn rbt-scroll-trigger fade_in animation-order-5">
                                <x-banner-button :banner="$banner" />
                            </div>
                        </div>
                        <div class="rbt-product-banner-img rbt-scroll-trigger zoom_in animation-order-1">
                            <img src="{{ asset($banner['image']) }}" alt="{{ $banner['highlight'] }}" loading="lazy" decoding="async">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
