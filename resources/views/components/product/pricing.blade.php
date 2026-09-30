@props(['product', 'discount' => true, 'stock' => false])

<div class="pricing-part">
    @if ($product->isOnSale())
        <del class="price-text">@money($product->compare_at_price)</del>
    @endif
    <span class="price-text">@money($product->price)@if ($product->price_max) - @money($product->price_max)@endif</span>
    @if ($product->saleQuantity()->priceSuffix())
        <span class="kova-price-unit">{{ $product->saleQuantity()->priceSuffix() }}</span>
    @endif
    @if ($discount && $product->isOnSale())
        <span class="rbt-offer-badge">-{{ $product->discountPercentage() }}%</span>
    @endif
    @if ($stock && ! $product->isSoldOut())
        @if ($product->hasLimitedStock())
            <div class="rbt-badge rbt-badge-bg-danger rbt-badge-border rbt-badge-small rbt-badge-rounded rbt-shiny">
                🔥 Stock limité</div>
        @else
            <div class="rbt-badge rbt-badge-bg-green rbt-badge-border rbt-badge-small rbt-badge-rounded">
                {{ $product->saleQuantity()->format($product->stock) }} en stock</div>
        @endif
    @endif
</div>
