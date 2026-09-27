@props(['product', 'inline' => false])

<div @class(['rbt-prd-qty-area', 'd-flex flex-row-reverse align-items-center rbt-gap--12' => $inline])>
    <p @class(['prd-qty-txt', 'text-nowrap' => $inline])>Plus que <strong>{{ $product->stock }}</strong> restant(s)</p>
    <div @class(['progress', 'mt--0' => $inline]) role="progressbar" aria-label="Stock restant" aria-valuenow="{{ $product->stockLeftPercentage() }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar" style="width: {{ $product->stockLeftPercentage() }}%"></div>
    </div>
</div>
