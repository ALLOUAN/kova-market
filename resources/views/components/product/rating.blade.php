@props(['rating', 'count'])

{{-- Rating from the approved customer reviews; nothing at all until the product has one. --}}
@if ($count > 0)
    <ul class="rbt-rating-icon-list" aria-label="Note : {{ number_format((float) $rating, 1, ',', ' ') }} sur 5, {{ $count }} avis">
        @for ($star = 1; $star <= 5; $star++)
            <li><i @class(['fa-solid fa-star', 'rbt-rated-icon' => $star <= round($rating)]) aria-hidden="true"></i></li>
        @endfor
    </ul>
    <p class="rating-digit">({{ $count }})</p>
@endif
