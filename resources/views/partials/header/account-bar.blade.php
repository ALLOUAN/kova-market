{{--
    Customer area bar, in place of the storefront header and top header: the area reads like an application. Logo,
    way back to the shop, cart, the customer and sign-out.
--}}
@php($customer = auth()->user())
<header class="kova-account-bar">
    <div class="container">
        <div class="kova-account-bar__inner">
            <a class="kova-account-bar__logo" href="{{ route('home') }}">
                <img src="{{ asset(config('storefront.logo_small')) }}" alt="{{ config('storefront.name') }}">
            </a>
            <span class="kova-account-bar__area">Espace client</span>

            <nav class="kova-account-bar__actions" aria-label="Raccourcis">
                <a class="kova-account-bar__link kova-account-bar__link--shop" href="{{ route('shop.index') }}">
                    <i class="fa-regular fa-arrow-left"></i><span>Retour à la boutique</span>
                </a>
                <a class="kova-account-bar__link kova-account-bar__link--cart" href="{{ route('cart.show') }}" aria-label="Mon panier ({{ $cartSummary->count() }})">
                    <i class="fa-regular fa-bag-shopping"></i><span>Panier</span>
                    @if ($cartSummary->count() > 0)
                        <span class="kova-account-bar__count">{{ $cartSummary->count() }}</span>
                    @endif
                </a>
                @if ($customer)
                    <span class="kova-account-bar__user">
                        <span class="kova-account-bar__avatar" aria-hidden="true">{{ Str::upper(Str::substr($customer->name, 0, 1)) }}</span>
                        <span>{{ Str::before($customer->name, ' ') }}</span>
                    </span>
                    <a class="kova-account-bar__link kova-account-bar__link--logout" href="#" data-logout>
                        <i class="fa-regular fa-right-from-bracket"></i><span>Se déconnecter</span>
                    </a>
                @endif
            </nav>
        </div>
    </div>
</header>
