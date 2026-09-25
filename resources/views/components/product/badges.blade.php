@props(['product'])

@if ($product->isSoldOut() || $product->badges)
    <div class="rbt-badge-wrapper rbt-content-top-left">
        @if ($product->isSoldOut())
            <div class="rbt-product-badge rbt-product-badge-bg-disabled border-rounded">Sold Out</div>
        @endif
        @foreach ($product->badges ?? [] as $badge)
            <div class="rbt-product-badge rbt-product-badge-bg-{{ $badge['variant'] }} border-rounded">{{ $badge['label'] }}</div>
        @endforeach
    </div>
@endif
