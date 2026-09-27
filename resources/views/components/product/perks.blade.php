@props(['product'])

@php
    $perks = array_filter([
        $product->sold_count >= 90 ? ['fa-bag-shopping', '90+ vendus récemment'] : null,
        $product->free_shipping ? ['fa-truck', 'Livraison offerte'] : null,
        $product->return_days ? ['fa-rotate-left', $product->return_days.' jours pour changer d’avis'] : null,
    ]);
@endphp

@if (count($perks) > 1)
    <div class="rbt-text-swiper-container rbt-arrow-vertical">
        <div class="swiper-wrapper">
            @foreach ($perks as [$icon, $label])
                <div class="swiper-slide">
                    <div class="rbt-text-group"> <span class="icon mr--4"><i class="fa-solid {{ $icon }}"></i></span>
                        {{ $label }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="rbt-verticle-arrow rbt-arrow-prev">
            <i class="fa-regular fa-chevron-up"></i>
        </div>
        <div class="rbt-verticle-arrow rbt-arrow-next">
            <i class="fa-regular fa-chevron-down"></i>
        </div>
    </div>
@elseif ($perks)
    @foreach ($perks as [$icon, $label])
        <div class="rbt-text-group"> <span class="icon mr--4"><i class="fa-solid {{ $icon }}"></i></span>
            {{ $label }}
        </div>
    @endforeach
@endif
