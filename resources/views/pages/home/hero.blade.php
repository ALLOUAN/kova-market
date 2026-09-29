<div class="rbt-component-area rbt-product-banner-area rbt-section-gap2 rbt-bg-color-gray-light rbt-elctro-hero-banner">
    <div class="container">
        <div class="row row--12 mt_dec--24">
            <div class="col-lg-12 col-md-12 col-sm-12 col-12 mt--24 d-flex justify-content-center">
                <div class="rbt-swiper-container-one rbt-arrow-between">
                    <div class="swiper rbt-hero-banner-activation-1 rbt-dot-bottom-center rbt-slideshow-content-inner">
                        <div class="swiper-wrapper">
                            @foreach ($hero as $slide)
                                <div class="swiper-slide">
                                    {{-- Every other slide gets the curved right corner. --}}
                                    <div @class(['rbt-product-banner rbt-product-banner-style-four rbt-banner-four-var-one rbt-curved-style-box rbt-scroll-trigger fade_in', 'animation-order-'.($loop->index % 4 + 1), 'rbt-curved-style-box-2' => $loop->even])>
                                        <div class="rbt-banner-inner">
                                            <div class="rbt-product-banner-img rbt-full-width-img rbt-scroll-trigger zoom_in animation-order-{{ $loop->index % 4 + 1 }}">
                                                {{-- The first slide is the home page's largest element (LCP): loaded first; the others wait. --}}
                                                <img src="{{ asset($slide['image']) }}" alt="{{ $slide['highlight'] }} {{ $slide['title'] }}" @if ($loop->first) fetchpriority="high" @else loading="lazy" decoding="async" @endif>
                                            </div>
                                            <div class="rbt-product-banner-content">
                                                <div class="rbt-content-section">
                                                    <p class="rbt-banner-subtitle mb-0">{{ $slide['subtitle'] }}</p>
                                                    <h2 class="rbt-banner-title rbt-banner-title-lg mb-0"><span class="rbt-bold--text">{{ $slide['highlight'] }}</span> {{ $slide['title'] }}</h2>
                                                    @if (filled($slide['price']))
                                                    <div class="rbt-pricing-part">
                                                        @if (filled($slide['compare_at_price']))
                                                        <del class="rbt-dis-price-text">@money($slide['compare_at_price'])</del>
                                                        @endif
                                                        <span class="d-flex align-items-center rbt-gap--8">
                                                            <span class="rbt-price-text offer-price">@money($slide['price'])</span>
                                                            <span class="rbt-offer-badge">{{ $slide['badge'] }}</span>
                                                        </span>
                                                    </div>
                                                    @endif
                                                    <div class="rbt-banner-btn">
                                                        <a class="rbt-btn rbt-btn-round rbt-magnetic-button" href="{{ $slide['url'] ?? '#' }}"><i class="fa-solid fa-arrow-up-right"></i>
                                                            ACHETER <br> MAINTENANT</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if ($loop->even)
                                            <div class="rbt-curved-portion rbt-right-corner-portion">
                                                <div class="rbt-wrapper"></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="rbt-swiper-pagination rbt-swiper-pagination-var-one"></div>
                    </div>
                    <div class="rbt-swiper-arrow rbt-arrow-left rbt-arrow-gray rbt-arrow-lg">
                        <div class="custom-overflow">
                            <i class="rbt-icon fa-regular fa-arrow-left"></i>
                            <i class="rbt-icon-top fa-regular fa-arrow-left"></i>
                        </div>
                    </div>
                    <div class="rbt-swiper-arrow rbt-arrow-right rbt-arrow-gray rbt-arrow-lg">
                        <div class="custom-overflow">
                            <i class="rbt-icon fa-regular fa-arrow-right"></i>
                            <i class="rbt-icon-top fa-regular fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
