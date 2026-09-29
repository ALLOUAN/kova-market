@use('App\Services\Storefront\ImageOptimizer')
@props(['product', 'order' => 1])

{{-- Large horizontal card used to spotlight one product. --}}
<div class="rbt-card rbt-product-card rbt-list-view-variation rbt-list-view-lg">
    <div class="inner rbt-scroll-trigger fade_in animation-order-{{ $order }}">
        <div class="rbt-card-img rbt-bg-color-default order-2">
            <a href="{{ $product->url() }}"><img class="rbt-prd-img" src="{{ asset($product->image) }}" @if ($srcset = ImageOptimizer::srcset($product->image)) srcset="{{ $srcset }}" sizes="(max-width: 575px) 50vw, 300px" @endif alt="{{ $product->name }}" loading="lazy" decoding="async"></a>
            <x-product.badges :product="$product" />
            @if ($product->watchers_count)
                <div class="rbt-discount-badge right--corner-style tooltips" data-tooltip="👁️ {{ $product->watchers_count }} personnes regardent ce produit" data-tooltip-position="bottom">
                    <span><i class="fa-regular fa-eye"></i>{{ $product->watchers_count }}</span>
                </div>
            @endif
            <x-product.quick-actions :product="$product" />
            @if ($product->hasCountdown())
                <div class="rbt-countdown-wrap rbt-content-bottom-center rbt-countdown-one bg-variation-black cd-border-style">
                    <x-countdown :date="$product->sale_ends_at" />
                </div>
            @endif
        </div>
        <div class="rbt-card-body order-1">
            <x-product.color-swatches :product="$product" />
            <a href="{{ $product->category->url() }}" class="rbt-card-subtitle rbt-card-catagories-text">{{ $product->category->name }}</a>
            <h2 class="rbt-card-title h4"><a href="{{ $product->url() }}">{{ $product->name }}</a></h2>
            <div class="rbt-card-rating">
                <x-product.rating :rating="$product->rating" :count="$product->reviews_count" />
            </div>
            <x-product.pricing :product="$product" />
            @unless ($product->isSoldOut())
                <x-product.stock-progress :product="$product" inline />
            @endunless
            <x-product.actions :product="$product" />
        </div>
    </div>
</div>
