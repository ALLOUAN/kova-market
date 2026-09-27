<div id="rbt-product-block-01" class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-gray-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="rbt-component-section-title d-flex flex-row justify-content-between align-items-center p-0 mb--32 mb_sm--16 border-0">
                    <h2 class="rbt-title rbt-scroll-trigger fade_in animation-order-1 h4"><span class="rbt-bold--text">{{ $dealsOfTheDay->name }}</span></h2>
                    <div class="mobile-horizontal-scroll-section">
                        <div class="rbt-product-nav-section rbt-nav-effect-activation rbt-scroll-trigger fade_in animation-order-2">
                            <ul class="rbt-product-nav-grp">
                                @foreach (config('homepage.deals_filters') as $filter)
                                    <li><a href="#" @class(['rbt-product-nav', 'active' => $loop->first])>{{ $filter }}</a></li>
                                @endforeach
                            </ul>
                            <ul class="rbt-product-nav-grp">
                                <li><a href="#" class="rbt-product-nav">Tout voir</a></li>
                            </ul>
                            <span class="rbt-bg-highlight"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row row--12 mt_dec--24">
            @foreach ($dealsOfTheDay->products as $product)
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-6 mt--24">
                    <x-product.card :product="$product" :order="$loop->iteration" shadow details />
                </div>
            @endforeach
        </div>
    </div>
</div>
