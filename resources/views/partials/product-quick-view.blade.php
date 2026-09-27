{{-- Quick view fragment (EX-18), served by products.quick-view and injected into the quick view modal or panel. --}}
@php
    $images = collect([$product->image, $product->hover_image])
        ->merge(collect($product->colors ?? [])->pluck('image'))
        ->filter()->unique()->values();
    $summary = Str::limit(trim(strip_tags(Str::sanitizeHtml((string) $product->description))), 220, '…');
@endphp

<div class="row g-4">
    <div class="col-md-6">
        <div class="rbt-bg-color-gray-light rbt-radius text-center p-4">
            <img class="img-fluid" src="{{ asset($images->first()) }}" alt="{{ $product->name }}" data-gallery-main>
        </div>
        @if ($images->count() > 1)
            <ul class="d-flex flex-wrap gap-2 list-unstyled mt--16 mb-0" aria-label="Photos">
                @foreach ($images as $image)
                    <li>
                        <button type="button" class="d-block border-0 rbt-bg-color-gray-light rbt-radius p-2" style="width: 72px" data-gallery-thumb="{{ asset($image) }}" aria-label="Photo {{ $loop->iteration }}">
                            <img class="img-fluid" src="{{ asset($image) }}" alt="" loading="lazy">
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="col-md-6">
        @if ($product->brand)
            <a class="b3 rbt-text-color-primary" href="{{ $product->brand->url() }}">{{ $product->brand->name }}</a>
        @endif
        <h2 class="h4 mt--8 mb--16" id="quickviewModalLabel"><a href="{{ $product->url() }}">{{ $product->name }}</a></h2>

        @if ($product->reviews_count > 0)
            <div class="rbt-review d-flex align-items-center gap-2 mb--16">
                <x-product.rating :rating="$product->rating" :count="$product->reviews_count" />
            </div>
        @endif

        @if ($summary)
            <p class="b3 mb--16">{{ $summary }}</p>
        @endif

        <x-product.purchase :product="$product" :variants="$variants" :options="$options" prefix="quick-view" :open="config('storefront.product_card.cart_action')" />

        <a class="d-inline-block b3 mt--24" href="{{ $product->url() }}">Voir la fiche complète <i class="fa-regular fa-arrow-right ml--4"></i></a>
    </div>
</div>
