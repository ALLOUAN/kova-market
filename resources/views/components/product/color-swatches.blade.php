@props(['product'])

{{-- color-swatches.js swaps the card image with the swatch "data-src" on click. --}}
@if ($product->colors)
    <div {{ $attributes->class(['rbt-color-select-area']) }}>
        <ul class="rbt-switcher-color-list product-switcher-activation">
            @foreach ($product->colors as $color)
                <li @if ($loop->first) class="active" @endif><a class="rbt-switcher--color tooltips" data-switcher-color="{{ $color['hex'] }}" data-src="{{ asset($color['image'] ?? $product->image) }}" data-tooltip="{{ $color['name'] }}" data-tooltip-position="top" href="#">
                        <div class="rbt-color-circle"></div>
                    </a></li>
            @endforeach
        </ul>
        @if ($product->variants_count > count($product->colors))
            <a class="prd-link-text" href="{{ $product->url() }}">+{{ $product->variants_count - count($product->colors) }} More
                Items</a>
        @endif
    </div>
@endif
