@props(['brand', 'order' => 1])

<div class="rbt-brand text-center style-one rbt-content-transform-style rbt-scroll-trigger fade_in animation-order-{{ $order }}">
    <a href="{{ $brand->url() }}">
        <div class="rbt-brand-inner">
            <div class="brand-image">
                <img src="{{ asset($brand->logo) }}" alt="{{ $brand->name }}">
                <span class="rbt-divider-arrow has-right-angel-animation"></span>
            </div>
            <div class="rbt-content">
                <span class="discount-text">{{ $brand->promo_label }}</span>
                <span class="prd-text">Total <span class="prd-number">{{ $brand->products_count }}</span> Products</span>
            </div>
        </div>
    </a>
</div>
