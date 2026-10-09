{{-- "Nouveautés" / "Populaires" (F-011, F-012): a title, a link to the full sorted list and a row of product cards on a
     panel in a colour of the logo: orange for what is new, soft gold for what sells. --}}
@if ($row['products']->isNotEmpty())
    @php($panel = $listId === 'new_arrivals' ? 'orange' : 'gold')
    <div id="rbt-product-row-{{ Str::slug($listId) }}" class="rbt-component-area rbt-catagories-area rbt-section-gap2" data-analytics-list="{{ json_encode(['item_list_id' => $listId, 'item_list_name' => $row['title']]) }}">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="rbt-component-section-title d-flex flex-row justify-content-between align-items-center p-0 mb--32 mb_sm--16 border-0">
                        <h2 class="rbt-title h4"><span class="rbt-bold--text">{{ $row['title'] }}</span></h2>
                        <a href="{{ $row['url'] }}" class="rbt-btn-link rbt-text-bold">Tout voir <i class="fa-regular fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="kova-panel kova-panel--{{ $panel }} kova-panel--on-white">
                <div class="row row--12 mt_dec--24">
                    @foreach ($row['products'] as $product)
                        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-6 mt--24">
                            <x-product.card :product="$product" :order="$loop->iteration" shadow details />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
