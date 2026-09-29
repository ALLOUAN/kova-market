@props(['promotion'])

<div class="rbt-card rbt-offer-card">
    <div class="inner">
        <div class="rbt-card-img">
            <a href="#">
                <img src="{{ asset($promotion->image) }}" alt="{{ $promotion->title }}" loading="lazy" decoding="async">
            </a>
        </div>
        <div class="rbt-card-body">
            <div class="ofr-meta-part">
                <div class="single-meta">
                    <i class="fa-sharp fa-regular fa-calendar"></i>
                    {{ $promotion->starts_at->format('j M Y') }} - {{ $promotion->ends_at->format('j M Y') }}
                </div>
                <div class="single-meta">
                    <a href="#">
                        <i class="fa-regular fa-shop"></i>
                        {{ $promotion->location_label }}
                    </a>
                </div>
            </div>

            <hr class="rbt-separator rbt-separator-gray200 mt--16 mb--12 rbt-bg-color-gray-100">
            <div class="rbt-ofr-card-content text-center mb--8">
                <h3 class="rbt-ofr-card-title mb--8 rbt-text-semi-bold h6">
                    <a href="#">{{ $promotion->title }}</a>
                </h3>
                <p class="rbt-ofr-card-text mb--12 b1 rbt-text-color-gray-500">{{ $promotion->description }}</p>
                <a class="rbt-btn rbt-btn-md active" href="#">Voir le détail</a>
            </div>
        </div>
    </div>
</div>
