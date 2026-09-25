@props(['product'])

{{-- Expandable specifications and delivery information under a product card. --}}
<div class="prd-details-area rbt-has-show-more">
    <div class="wrapper rbt-has-show-more-inner-content">
        @if ($product->specifications)
            <ul class="product-details-list">
                @foreach ($product->specifications as $spec)
                    <li>
                        <span class="rbt-bold--text">{{ $spec['label'] }} :</span>
                        @foreach (explode("\n", $spec['value']) as $line)
                            <span @class(['text', 'd-block' => ! $loop->first])> {{ $line }}</span>
                        @endforeach
                    </li>
                @endforeach
            </ul>
        @endif
        <ul class="product-details-list shipment-details-list">
            <li>
                <span class="icon"><i class="fa-sharp fa-regular fa-truck"></i></span>
                <div class="right-content">
                    <span class="rbt-bold--text">Ships :</span>
                    <span class="text">{{ config('storefront.shipping.delay') }}</span>
                    <br>
                    <a href="#" class="shipment-quick-link rbt-btn-link">Get delivery dates</a>
                </div>
            </li>
            <li>
                <span class="icon"><i class="fa-regular fa-bag-shopping"></i></span>
                <div class="right-content">
                    <span class="rbt-bold--text">Pickup :</span>
                    <a href="#" class="shipment-quick-link rbt-btn-link">Check Availability</a>
                </div>
            </li>
        </ul>
    </div>
    <div class="rbt-show-more-btn-area">
        <button class="rbt-show-more-btn">Show More</button>
    </div>
</div>
