@props(['rating', 'count'])

<ul class="rbt-rating-icon-list">
    @for ($star = 1; $star <= 5; $star++)
        <li><i @class(['fa-solid fa-star', 'rbt-rated-icon' => $star <= round($rating)])></i></li>
    @endfor
</ul>
<p class="rating-digit">({{ $count }})</p>
