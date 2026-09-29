@use('App\Services\Storefront\ImageOptimizer')
@props(['product', 'size' => 'sm', 'heading' => 'h2', 'order' => 1])

{{-- Compact horizontal product card (text left, image right). --}}
<div @class(['rbt-card rbt-product-card rbt-list-view-variation', 'rbt-list-view-sm' => $size === 'sm', 'list-view-md' => $size === 'md'])>
    <div class="inner rbt-scroll-trigger fade_in animation-order-{{ $order }}">
        <div class="rbt-card-body">
            <div class="rbt-card-rating">
                <x-product.rating :rating="$product->rating" :count="$product->reviews_count" />
            </div>
            <{{ $heading }} class="rbt-card-title h6"><a href="{{ $product->url() }}">{{ $product->name }}</a></{{ $heading }}>
            <x-product.pricing :product="$product" :discount="false" />
        </div>
        <div class="rbt-card-img rbt-bg-color-default rbt-curved-style-box">
            <a href="{{ $product->url() }}"><img src="{{ asset($product->image) }}" @if ($srcset = ImageOptimizer::srcset($product->image)) srcset="{{ $srcset }}" sizes="120px" @endif alt="{{ $product->name }}" loading="lazy" decoding="async"></a>
        </div>
    </div>
</div>
