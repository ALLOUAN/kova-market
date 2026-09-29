@props(['promotion'])

{{-- A campaign of the "Offres spéciales" panel; it links to its offer only when the back-office gave it a link. --}}
@php($link = filled($promotion->url) ? $promotion->url : null)

<div class="rbt-card rbt-offer-card">
    <div class="inner">
        <div class="rbt-card-img">
            @if ($link)<a href="{{ $link }}">@endif
                <img src="{{ asset($promotion->image) }}" alt="{{ $promotion->title }}" loading="lazy" decoding="async">
            @if ($link)</a>@endif
        </div>
        <div class="rbt-card-body">
            <div class="ofr-meta-part">
                <div class="single-meta">
                    <i class="fa-sharp fa-regular fa-calendar"></i>
                    {{ $promotion->starts_at->translatedFormat('j M Y') }} - {{ $promotion->ends_at->translatedFormat('j M Y') }}
                </div>
                <div class="single-meta">
                    <i class="fa-regular fa-shop"></i>
                    {{ $promotion->location_label }}
                </div>
            </div>

            <hr class="rbt-separator rbt-separator-gray200 mt--16 mb--12 rbt-bg-color-gray-100">
            <div class="rbt-ofr-card-content text-center mb--8">
                <h3 class="rbt-ofr-card-title mb--8 rbt-text-semi-bold h6">
                    @if ($link)<a href="{{ $link }}">{{ $promotion->title }}</a>@else{{ $promotion->title }}@endif
                </h3>
                <p class="rbt-ofr-card-text mb--12 b1 rbt-text-color-gray-500">{{ $promotion->description }}</p>
                @if ($link)
                    <a class="rbt-btn rbt-btn-md active" href="{{ $link }}">Voir le détail</a>
                @endif
            </div>
        </div>
    </div>
</div>
