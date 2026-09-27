<div class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 pr--0">
                <div class="rbt-component-section-title d-flex justify-content-between flex-row align-items-center p-0 mb--32 mb_sm--16 border-0">
                    <h2 class="rbt-title rbt-scroll-trigger fade_in animation-order-1 h4"><span class="rbt-bold--text">Catégories populaires</span></h2>
                    <a class="rbt-btn rbt-btn-secondary rbt-btn-sm-2 rbt-scroll-trigger fade_in animation-order-2 animated-icon-btn defalt-secondary-bg" href="#">
                        <span class="btn-text">Toutes les catégories</span>
                        <span class="animated-icon ml--4">
                            <x-icons.external-arrow />
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="rbt-catagories-section rbt-curved-style-box rbt-catagories-section-bg-one">
            <div class="row row--12 mt_dec--24">
                <div class="col-xl-8 col-lg-12 col-12 mt--24">
                    <div class="row row--12 mt_dec--24 rbt-mobile-row">
                        @foreach ($categories as $category)
                            <div class="col-lg-4 col-md-6 col-sm-6 col-6 mt--24">
                                <x-category.card :category="$category" :order="$loop->iteration" />
                            </div>
                        @endforeach
                    </div>
                </div>

                @php($banner = $banners['categories'])
                <div class="col-xl-4 col-lg-12 col-12 mt--24">
                    <div class="rbt-cat-box banner-card text-center rbt-curved-style-box rbt-catagories-img-bg rbt-scroll-trigger fade_in animation-order-5">
                        <div class="inner">
                            <div class="content">
                                <p class="subtitle rbt-scroll-trigger fade_in animation-order-1">{{ $banner['subtitle'] }}</p>
                                <h2 class="rbt-title rbt-scroll-trigger fade_in animation-order-2 h4"><a href="#"><span class="rbt-bold--text">{{ $banner['highlight'] }}</span> {{ $banner['title'] }}</a></h2>
                                <h3 class="secondary-title rbt-scroll-trigger fade_in animation-order-3">{{ $banner['tagline'] }}</h3>
                            </div>
                            <div class="rbt-image-portion">
                                <a href="#">
                                    <img class="rbt-scroll-trigger zoom_in animation-order-4" src="{{ asset($banner['image']) }}" alt="{{ $banner['highlight'] }}">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
