<div id="rbt-product-block-04" class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-white" data-analytics-list="{{ json_encode(['item_list_id' => 'featured-products', 'item_list_name' => (string) $featuredTitle]) }}">
    <div class="container">
        <div class="rbt-fshape-box-outline-style rbt-fshape-box-outline-style-extend-width">
            <div class="row">
                <div class="col-lg-12">
                    <x-section-title>{{ $featuredTitle }}</x-section-title>
                </div>
            </div>
            <div class="rbt-fshape-box rbt-bg-color-gray-light">
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
</div>
