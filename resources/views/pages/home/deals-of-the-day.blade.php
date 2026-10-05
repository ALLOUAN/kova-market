<div id="rbt-product-block-01" class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-gray-light" data-analytics-list="{{ json_encode(['item_list_id' => $dealsOfTheDay->slug, 'item_list_name' => $dealsOfTheDay->name]) }}">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="rbt-component-section-title d-flex flex-row justify-content-between align-items-center p-0 mb--32 mb_sm--16 border-0">
                    <h2 class="rbt-title rbt-scroll-trigger fade_in animation-order-1 h4"><span class="rbt-bold--text">{{ $dealsOfTheDay->name }}</span></h2>
                    {{-- A curated selection: its whole list on its own page. --}}
                    <a href="{{ route('collections.show', $dealsOfTheDay) }}" class="rbt-btn-link rbt-text-bold">Tout voir <i class="fa-regular fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        {{-- The cards sit on a night blue panel, like the green one of "Catégories populaires". --}}
        <div class="kova-panel kova-panel--navy">
            <div class="row row--12 mt_dec--24">
                @foreach ($dealsOfTheDay->products as $product)
                    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-6 mt--24">
                        <x-product.card :product="$product" :order="$loop->iteration" shadow details />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
