@use('App\Services\Storefront\ImageOptimizer')
@props([
    'product',
    'heading' => 'h3',
    'order' => 1,
    'shadow' => false,
    'details' => false,
    'progress' => false,
])

{{-- Grid product card. "progress" swaps the stock badge for the "only N left" bar; "details" adds the expandable specs. --}}
<div @class(['rbt-card rbt-product-card', 'rbt-stock-out-product-card' => $product->isSoldOut(), 'has-hover-box-shadow' => $shadow]) data-analytics-item="{{ json_encode(\App\Services\Storefront\Analytics::listItem($product)) }}">
    <div class="inner rbt-scroll-trigger fade_in animation-order-{{ $order }}">
        <div @class(['rbt-card-img rbt-bg-color-default', 'rbt-has-hover-video' => $product->hover_video, 'rbt-has-hover-img' => ! $product->hover_video && $product->hover_image])>
            <a href="{{ $product->url() }}">
                <img class="rbt-prd-img" src="{{ asset($product->image) }}" @if ($srcset = ImageOptimizer::srcset($product->image)) srcset="{{ $srcset }}" sizes="(max-width: 575px) 50vw, 300px" @endif alt="{{ $product->name }}" loading="lazy" decoding="async">
                @if ($product->hover_video)
                    <video class="rbt-hover-video" src="{{ asset($product->hover_video) }}" muted loop autoplay></video>
                @elseif ($product->hover_image)
                    <img class="rbt-hover-img" src="{{ asset($product->hover_image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                @endif
            </a>
            <x-product.badges :product="$product" />
            @if ($product->watchers_count)
                {{-- Real visits of the product page over the last minutes (App\Services\Storefront\ProductViewers). --}}
                <div class="rbt-discount-badge right--corner-style tooltips" data-tooltip="👁️ {{ $product->watchers_count }} personnes ont vu ce produit ces {{ \App\Services\Storefront\ProductViewers::WINDOW_MINUTES }} dernières minutes" data-tooltip-position="bottom" aria-label="{{ $product->watchers_count }} personnes ont vu ce produit récemment">
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
        <div class="rbt-card-body">
            @unless ($product->isSoldOut())
                {{-- "+": the default variant straight to the cart, one click, whatever the options. --}}
                <form method="POST" action="{{ route('cart.items.store') }}" data-cart-form class="kova-quick-add">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="open" value="{{ config('storefront.product_card.cart_action') }}">
                    <button type="submit" class="kova-quick-add__btn tooltips" data-tooltip="Ajouter au panier" data-tooltip-position="left" aria-label="Ajouter « {{ $product->name }} » au panier"><i class="fa-regular fa-plus"></i></button>
                </form>
            @endunless
            <x-product.color-swatches :product="$product" />
            <a href="{{ $product->category->url() }}" class="rbt-card-subtitle rbt-card-catagories-text">{{ $product->category->name }}</a>
            <{{ $heading }} class="rbt-card-title h6"><a href="{{ $product->url() }}">{{ $product->name }}</a></{{ $heading }}>
            <div class="rbt-card-rating">
                <x-product.rating :rating="$product->rating" :count="$product->reviews_count" />
                <x-product.perks :product="$product" />
            </div>
            <x-product.pricing :product="$product" :stock="! $progress" />
            @if ($progress && ! $product->isSoldOut())
                <x-product.stock-progress :product="$product" />
            @endif
            <x-product.actions :product="$product" />
        </div>
    </div>
    @if ($details)
        <x-product.details :product="$product" />
    @endif
</div>
