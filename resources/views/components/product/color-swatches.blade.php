@props(['product'])

{{-- A click on a swatch shows that colour's picture on the card (main.min.js swaps the image's src from "data-src",
     assets/js/storefront.js its srcset from "data-srcset"). A colour without its own picture keeps the one shown. --}}
@if ($product->colors)
    <div {{ $attributes->class(['rbt-color-select-area']) }}>
        <ul class="rbt-switcher-color-list product-switcher-activation">
            @foreach ($product->colors as $color)
                @php($picture = $color['image'] ?? null)
                <li @if ($loop->first) class="active" @endif><a class="rbt-switcher--color tooltips" data-switcher-color="{{ $color['hex'] }}" @if ($picture) data-src="{{ asset($picture) }}" data-srcset="{{ \App\Services\Storefront\ImageOptimizer::srcset($picture) }}" @endif data-tooltip="{{ $color['name'] }}" data-tooltip-position="top" href="#" aria-label="{{ $color['name'] }}">
                        <div class="rbt-color-circle"></div>
                    </a></li>
            @endforeach
        </ul>
    </div>
@endif
