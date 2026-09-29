{{-- Bottom of the sign-in and sign-up windows: the customer testimonials published from the back-office
     (Contenus › Témoignages clients), else the store's guarantees. --}}
<div class="rbt-login-form-bottom rbt-swiper-container-pagination position-relative">
    @if ($testimonials->isNotEmpty())
        <div class="swiper rbt-log-slide-activation pb--40">
            <div class="swiper-wrapper">
                @foreach ($testimonials as $testimonial)
                    <div class="swiper-slide">
                        <div class="rbt-client-review">
                            <ul class="rbt-rating-icon-list d-flex justify-content-center" aria-label="Note : {{ $testimonial->rating }} sur 5">
                                @for ($star = 1; $star <= 5; $star++)
                                    <li><i @class(['fa-star', 'fa-solid rbt-rated-icon' => $star <= $testimonial->rating, 'fa-regular' => $star > $testimonial->rating]) aria-hidden="true"></i></li>
                                @endfor
                            </ul>
                            <p class="rbt-review-text mt--8 mb--12">« {{ $testimonial->content }} »</p>
                            <div class="d-flex flex-wrap justify-content-center align-items-center rbt-gap--8">
                                <p class="mb--0 h6">{{ $testimonial->author_name }}@if ($testimonial->city)<span class="b4 fw-normal"> · {{ $testimonial->city }}</span>@endif</p>
                                @if ($testimonial->is_verified)
                                    <div class="rbt-verified-badge badge-rounded">
                                        <i class="fa-sharp fa-solid fa-shield-check" aria-hidden="true"></i>
                                        Client vérifié
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="swiper-pagination rbt-swiper-progress rbt-swiper-pagination-dot-extend"></div>
        </div>
    @else
        <ul class="kova-guarantees">
            <li><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> {{ config('storefront.shipping.delay') ?: 'Livraison rapide à Abidjan' }}</li>
            @php($networks = collect(config('storefront.payment_methods'))->filter(fn ($method) => filled($method['logo'] ?? null) && file_exists(public_path($method['logo']))))
            <li>
                <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i>
                @if ($networks->isNotEmpty())
                    <span class="kova-network-logos" aria-label="Paiement par {{ $networks->pluck('label')->join(', ', ' ou ') }}">
                        @foreach ($networks as $network)
                            <img src="{{ asset($network['logo']) }}" alt="{{ $network['label'] }}" title="{{ $network['label'] }}" height="28" loading="lazy" decoding="async">
                        @endforeach
                    </span>
                @else
                    Orange Money, MTN MoMo, Moov Money, Wave
                @endif
            </li>
            <li><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i> Ou paiement à la livraison</li>
        </ul>
    @endif
</div>
