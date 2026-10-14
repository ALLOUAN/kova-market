@props(['product', 'inline' => false])

{{-- Stock left on a product card, in the product's own unit (pieces, kg, packs…). At or under the limited-stock
     threshold it reads as a warning ("Plus que 3 en stock", red bar); otherwise it simply states the stock. The bar
     shows the share of the initial stock still available. --}}
@php
    $limited = $product->hasLimitedStock();
    $left = $product->saleQuantity()->format($product->stock);
    $share = $product->stockLeftPercentage();
@endphp
<div @class(['rbt-prd-qty-area', 'is-limited' => $limited, 'd-flex flex-row-reverse align-items-center rbt-gap--12' => $inline])>
    <p @class(['prd-qty-txt', 'text-nowrap' => $inline])>
        @if ($limited)
            Plus que <strong>{{ $left }}</strong> en stock
        @else
            <strong>{{ $left }}</strong> en stock
        @endif
    </p>
    <div @class(['progress', 'mt--0' => $inline]) role="progressbar" aria-label="Stock disponible" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100" aria-valuetext="{{ $left }} en stock">
        <div class="progress-bar" style="width: {{ $share }}%"></div>
    </div>
</div>
