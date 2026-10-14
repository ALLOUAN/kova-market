{{--
    The picture of a home page banner: its main image, or, when the back-office added further ones, all of them
    shown in turn (cross-fade, assets/js/storefront.js [data-banner-slides]). $class goes on the image(s).
--}}
@props(['banner', 'class' => null])
@php($slides = $banner['images'] ?? [$banner['image']])
@if (count($slides) > 1)
    <div class="kova-banner-slides" data-banner-slides>
        @foreach ($slides as $slide)
            <img @class([$class, 'kova-banner-slides__img', 'is-active' => $loop->first]) src="{{ asset($slide) }}" alt="{{ $loop->first ? $banner['highlight'] : '' }}" loading="lazy" decoding="async" @unless ($loop->first) aria-hidden="true" @endunless>
        @endforeach
    </div>
@else
    <img @class([$class]) src="{{ asset($slides[0] ?? $banner['image']) }}" alt="{{ $banner['highlight'] }}" loading="lazy" decoding="async">
@endif
