<div id="rbt-product-block-04" class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-white" data-analytics-list="{{ json_encode(['item_list_id' => 'featured-products', 'item_list_name' => (string) $featuredTitle]) }}">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="rbt-component-section-title d-flex flex-row justify-content-between align-items-center p-0 mb--32 mb_sm--16 border-0">
                    <h2 class="rbt-title rbt-scroll-trigger fade_in animation-order-1 h4"><span class="rbt-bold--text">{{ $featuredTitle }}</span></h2>
                </div>
            </div>
        </div>
        {{-- The products sit on a night blue panel, like "Offres du jour". --}}
        <div class="kova-panel kova-panel--navy kova-panel--on-white">
            <div class="row row--12 mt_dec--24">
                <div class="col-xxl-8 col-xl-8 col-lg-12 col-md-12 col-sm-12 col-12 mt--24">
                    <x-product.featured-card :product="$featuredProduct" />
                </div>
                @if ($featuredProducts->isNotEmpty())
                    <div class="col-xxl-4 col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 mt--24">
                        <div class="rbt-list-card-box variation-lg">
                            <div class="row row--12 mt_dec--24 rbt-card-row-has-top-separator rbt-one-align-card-row">
                                @foreach ($featuredProducts as $product)
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 mt--24">
                                        <x-product.list-card :product="$product" size="md" :order="$loop->iteration + 1" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
