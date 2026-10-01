@props(['title', 'heading' => true])

{{-- Customer area frame: the customer and the menu on the left, the page on the right, flash messages on top.
     "heading" off for the dashboard, whose welcome card stands for the title. --}}
@php
    $customer = auth()->user();
    $links = [
        'account.show' => ['Tableau de bord', 'fa-grid-2', 'account.show', 'navy'],
        'account.orders' => ['Mes commandes', 'fa-bag-shopping', 'account.orders*', 'green'],
        'account.tracking' => ['Suivi de mes commandes', 'fa-truck-fast', 'account.tracking', 'gold'],
        'account.addresses.index' => ['Mes adresses', 'fa-location-dot', 'account.addresses.*', 'blue'],
    ];
@endphp

<div class="kova-account">
    <div class="container">
        @if ($heading)
            <div class="kova-account__heading">
                <nav aria-label="Fil d’Ariane" class="kova-account__trail">
                    <a href="{{ route('account.show') }}">Mon compte</a>
                    @unless (request()->routeIs('account.show'))
                        <i class="fa-regular fa-chevron-right" aria-hidden="true"></i><span aria-current="page">{{ $title }}</span>
                    @endunless
                </nav>
                <h1 class="kova-account__title">{{ $title }}</h1>
            </div>
        @endif

        <div class="row g-4">
            <aside class="col-lg-3 order-last order-lg-first">
                <div class="kova-account-nav">
                    @if ($customer)
                        <div class="kova-account-nav__profile">
                            <span class="kova-account-nav__avatar" aria-hidden="true">{{ Str::upper(Str::substr($customer->name, 0, 1)) }}</span>
                            <div class="kova-account-nav__who">
                                <strong>{{ $customer->name }}</strong>
                                <span>{{ $customer->phone ? \App\Support\PhoneNumber::format($customer->phone) : $customer->email }}</span>
                            </div>
                        </div>
                    @endif
                    <nav aria-label="Espace client">
                        <ul class="kova-account-nav__list">
                            @foreach ($links as $route => [$label, $icon, $pattern, $tone])
                                <li>
                                    <a @class(['kova-account-nav__link', 'kova-account-nav__link--'.$tone, 'is-active' => request()->routeIs($pattern)]) href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                                        <i class="fa-regular {{ $icon }}"></i>{{ $label }}
                                    </a>
                                </li>
                            @endforeach
                            @if (config('storefront.features.wishlist'))
                                <li><a @class(['kova-account-nav__link kova-account-nav__link--rose', 'is-active' => request()->routeIs('account.wishlist')]) href="{{ route('account.wishlist') }}" @if (request()->routeIs('account.wishlist')) aria-current="page" @endif><i class="fa-regular fa-heart"></i>Mes favoris</a></li>
                            @endif
                            <li class="kova-account-nav__sep"><a class="kova-account-nav__link kova-account-nav__link--quiet" href="#" data-logout><i class="fa-regular fa-right-from-bracket"></i>Se déconnecter</a></li>
                        </ul>
                    </nav>
                </div>
            </aside>
            <div class="col-lg-9">
                @foreach (['account_status' => 'success', 'account_error' => 'danger'] as $key => $type)
                    @if (session($key))
                        <div class="alert alert-{{ $type }} mb--24" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($key) }}</div>
                    @endif
                @endforeach
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
