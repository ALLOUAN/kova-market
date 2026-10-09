<div class="rbt-wrapper-middle rbt-header-middle-one">
    <div class="container">
        <div class="mainbar-row align-items-center">
            <div class="header-left">
                @include('partials.header.mobile-menu-button')
                <div class="rbt-header-content">
                    <div class="header-info">
                        @include('partials.header.logo')
                    </div>

                    <div class="header-info p-0 d-none d-xl-block ml--28">
                        @include('partials.header.burger', ['class' => 'rbt-offcanvas-trigger-transparent-btn'])
                    </div>
                </div>
            </div>

            <div class="rbt-header-content d-none d-xl-block">
                <div class="header-info">
                    <div class="rbt-search-with-category uni-header-swc-one">
                        <form action="{{ route('shop.index') }}" method="GET" role="search">
                            <div class="rbt-inner-search-field border-0">
                                <div class="rbt-search-input-section has-left-catagory-section rbt-inner-search-label-animate-activation">
                                    <div class="filter-select rbt-modern-select search-by-category">
                                        <select class="rbt-select-activation" name="category" data-live-search="true" data-live-search-placeholder="Rechercher une catégorie" aria-label="Catégorie">
                                            <option value="">Toutes les catégories</option>
                                            @foreach ($categoryTree as $category)
                                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <input type="text" name="q" aria-label="Rechercher">
                                    <span class="cd-headline clip is-full-width">
                                        <span class="cd-words-wrapper">
                                            @foreach (config('storefront.search.placeholders') as $placeholder)
                                                <b @class(['is-visible' => $loop->first, 'is-hidden' => ! $loop->first])>{{ $placeholder }}</b>
                                            @endforeach
                                        </span>
                                    </span>
                                </div>
                                <button class="rbt-round-btn search-btn" type="submit" aria-label="Rechercher"><i class="fa-sharp fa-solid fa-magnifying-glass"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="header-right">
                <ul class="rbt-quick-access">
                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-1 rbt-access-box-has-bg-hover d-none d-lg-flex">
                        <a href="{{ $contact['phone_href'] }}" class="rbt-access-box-wrapper">
                            <div class="rbt-round-btn rbt-bg-static-gray">
                                <i class="fa-regular fa-phone"></i>
                            </div>
                            <div class="content p-0">
                                <p>Service client</p>
                                <span>{{ $contact['phone'] }}</span>
                            </div>
                        </a>
                    </li>
                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 rbt-access-box-has-bg-hover d-none d-lg-flex">
                        @auth
                            {{-- "Mon compte" opens the customer area; hovering or tabbing into it shows the shortcuts. --}}
                            @php
                                $accountUser = auth()->user();
                                $ordersUnderway = $accountUser->orders()->whereNotIn('status', [\App\Enums\OrderStatus::Delivered, \App\Enums\OrderStatus::Cancelled])->count();
                            @endphp
                            <div class="kova-account-menu">
                                <a href="{{ route('account.show') }}" class="rbt-access-box-wrapper" aria-haspopup="true">
                                    <div class="rbt-round-btn rbt-bg-static-gray">
                                        <i class="fa-regular fa-user"></i>
                                    </div>
                                    <div class="content">
                                        <p>Bonjour, {{ Str::before($accountUser->name, ' ') }}</p>
                                        <span>Mon compte <i class="fa-regular fa-chevron-down kova-account-menu__caret" aria-hidden="true"></i></span>
                                    </div>
                                </a>
                                <div class="kova-account-menu__panel">
                                    <div class="kova-account-menu__head">
                                        <span class="kova-account-menu__avatar" aria-hidden="true">{{ Str::upper(Str::substr($accountUser->name, 0, 1)) }}</span>
                                        <span>
                                            <strong>{{ $accountUser->name }}</strong>
                                            <small>{{ $accountUser->email ?: \App\Support\PhoneNumber::format((string) $accountUser->phone) }}</small>
                                        </span>
                                    </div>
                                    <nav class="kova-account-menu__links" aria-label="Mon compte">
                                        <a href="{{ route('account.show') }}"><i class="fa-regular fa-gauge"></i>Tableau de bord</a>
                                        <a href="{{ route('account.orders') }}"><i class="fa-regular fa-box"></i>Mes commandes
                                            @if ($ordersUnderway > 0)<span class="kova-account-menu__count" title="En cours">{{ $ordersUnderway }}</span>@endif
                                        </a>
                                        <a href="{{ route('account.tracking') }}"><i class="fa-regular fa-truck-fast"></i>Suivre une livraison</a>
                                        @if (config('storefront.features.wishlist'))
                                            <a href="{{ route('account.wishlist') }}"><i class="fa-regular fa-heart"></i>Mes favoris
                                                @if ($wishlistCount > 0)<span class="kova-account-menu__count">{{ $wishlistCount }}</span>@endif
                                            </a>
                                        @endif
                                        <a href="{{ route('account.addresses.index') }}"><i class="fa-regular fa-location-dot"></i>Mes adresses</a>
                                        <a href="{{ route('cart.show') }}"><i class="fa-regular fa-bag-shopping"></i>Mon panier</a>
                                    </nav>
                                    @if ($accountUser->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                                        <a class="kova-account-menu__admin" href="{{ \Filament\Facades\Filament::getPanel('admin')->getUrl() }}"><i class="fa-regular fa-screwdriver-wrench"></i>Administration</a>
                                    @endif
                                    <a class="kova-account-menu__logout" href="#" data-logout><i class="fa-regular fa-right-from-bracket"></i>Se déconnecter</a>
                                </div>
                            </div>
                        @else
                            <a href="#!" class="rbt-access-box-wrapper" data-bs-toggle="modal" data-bs-target="#signinModal">
                                <div class="rbt-round-btn rbt-bg-static-gray">
                                    <i class="fa-regular fa-user"></i>
                                </div>
                                <div class="content">
                                    <p>Connexion / Inscription</p>
                                    <span>Accéder au compte</span>
                                </div>
                            </a>
                        @endauth
                    </li>
                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 rbt-access-box-has-bg-hover d-flex d-lg-none">
                        <a class="search-trigger-active rbt-round-btn rbt-bg-static-gray rbt-modern-close-btn" href="#" aria-label="Rechercher">
                            <i class="fa-regular fa-search search-icon"></i>
                            <div class="modern-close-wrapper"></div>
                        </a>
                    </li>
                    @if (config('storefront.features.compare'))
                        <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 rbt-access-box-has-bg-hover rbt-wishlist d-none d-lg-flex">
                            <a href="{{ route('compare.index') }}" class="rbt-access-box-wrapper" aria-label="Comparateur ({{ $compared->count() }})">
                                <div class="rbt-round-btn rbt-bg-static-gray">
                                    <i class="fa-regular fa-code-compare"></i>
                                    <span class="access-box-count" data-compare-count @if ($compared->isEmpty()) hidden @endif>{{ $compared->count() }}</span>
                                </div>
                            </a>
                        </li>
                    @endif
                    @if (config('storefront.features.wishlist'))
                        <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 rbt-access-box-has-bg-hover rbt-wishlist d-none d-lg-flex">
                            <a href="{{ auth()->check() ? route('account.wishlist') : route('wishlist.index') }}" class="rbt-access-box-wrapper" aria-label="Mes favoris ({{ $wishlistCount }})">
                                <div class="rbt-round-btn rbt-bg-static-gray">
                                    <i class="fa-regular fa-heart"></i>
                                    <span class="access-box-count" data-wishlist-count @if (! $wishlistCount) hidden @endif>{{ $wishlistCount }}</span>
                                </div>
                            </a>
                        </li>
                    @endif
                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 rbt-access-box-has-bg-hover rbt-mini-cart">
                        <a href="#" class="rbt-access-box-wrapper rbt-cart-sidenav-activation">
                            <div class="rbt-round-btn rbt-bg-static-gray">
                                <i class="fa-regular fa-bag-shopping"></i>
                                <span class="access-box-count rbt-shiny" data-cart-count>{{ $cartSummary->count() }}</span>
                            </div>
                            <div class="content p-0">
                                <p>Total du panier</p>
                                <span data-cart-total>@money($cartSummary->total())</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
