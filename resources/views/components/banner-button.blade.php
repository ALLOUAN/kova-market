@props(['banner'])

{{-- Round button of a home banner: its text from the back-office ("Acheter maintenant" by default), on two lines. --}}
@php
    $words = preg_split('/\s+/', trim($banner['button'] ?? \App\Models\Banner::DEFAULT_BUTTON)) ?: [];
    $half = (int) ceil(count($words) / 2);
@endphp
<a {{ $attributes->class(['rbt-btn rbt-btn-round rbt-magnetic-button text-uppercase']) }} href="{{ $banner['url'] }}" data-analytics-promotion="{{ json_encode(\App\Services\Storefront\Analytics::promotion($banner)) }}"><i class="fa-solid fa-arrow-up-right" aria-hidden="true"></i> {{ implode(' ', array_slice($words, 0, $half)) }}@if (count($words) > 1)<br>{{ implode(' ', array_slice($words, $half)) }}@endif</a>
