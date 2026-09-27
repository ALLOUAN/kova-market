@php
    $currencies = collect(config('storefront.currencies'));
    $currency = $currencies->firstWhere('code', config('storefront.currency')) ?? $currencies->first();
    $languages = collect(config('storefront.languages'));
    $language = $languages->firstWhere('code', app()->getLocale()) ?? $languages->first();
@endphp
<div class="rbt-topbar-section rbt-topbar-one">
    <div class="container">
        <div class="row align-items-center d-none d-md-flex mlr--0 row--0">
            <div class="col-lg-6 col-md-6 col-12">
                <div class="rbt-fancy-item fancy-menu-text fancy-menu-start">
                    <div class="rbt-fancy-text">
                        <strong>Tendances :</strong>
                        <div class="rbt-text-swiper-container rbt-arrow-vertical">
                            <div class="swiper-wrapper">
                                @foreach (config('storefront.announcements.trending') as $message)
                                    <div class="swiper-slide">{{ $message }}
                                        <a class="rbt-fancy-link ml--4" href="#">En savoir plus</a>
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
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-12">
                <div class="rbt-header-sec-col rbt-header-right rbt-fancy-item fancy-menu-address fancy-menu-end">
                    <div class="rbt-header-content m--0">
                        <ul class="rbt-quick-access d-none d-lg-flex">
                            <li class="rbt-access-box">
                                <div class="header-info">
                                    <a href="#" class="rbt-access-link">Notre adresse</a>
                                </div>
                                <div class="header-info">
                                    <a href="{{ route('tracking.show') }}" class="rbt-access-link">Suivre ma commande</a>
                                </div>
                                {{-- Switchers are only shown when there is something to switch to. --}}
                                @if ($currencies->count() > 1)
                                <div class="header-info">
                                    <ul class="rbt-dropdown-menu rbt-dropdown-menu-elastic currency-menu">
                                        <li class="has-child-menu">
                                            <a href="#">
                                                <span class="menu-item">{{ $currency['label'] }}</span>
                                                <i class="right-icon fa-regular fa-chevron-down"></i>
                                            </a>
                                            <ul class="sub-menu hover-reverse">
                                                @foreach ($currencies as $option)
                                                    <li>
                                                        <a href="#" @class(['active' => $option['code'] === $currency['code']])>
                                                            <span class="menu-item">{{ $option['label'] }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                                @endif
                                @if ($languages->count() > 1)
                                <div class="header-info">
                                    <ul class="rbt-dropdown-menu rbt-dropdown-menu-elastic switcher-language">
                                        <li class="has-child-menu">
                                            <a href="#">
                                                <img class="left-image" src="{{ asset($language['flag']) }}" alt="{{ $language['label'] }}">
                                                <span class="menu-item">{{ $language['label'] }}</span>
                                                <i class="right-icon fa-regular fa-chevron-down"></i>
                                            </a>
                                            <ul class="sub-menu ">
                                                @foreach ($languages as $option)
                                                    <li>
                                                        <a href="#" @class(['active' => $option['code'] === $language['code']])>
                                                            <img class="left-image" src="{{ asset($option['flag']) }}" alt="{{ $option['label'] }}">
                                                            <span class="menu-item">{{ $option['label'] }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
