<div class="rbt-toolbar rbt-toolbar--bottom d-block d-xl-none">
    <div class="container p--0">
        <div class="row row row--0">
            <div class="col-md-12">
                <ul class="rbt-quick-access onepagenav">
                    <li class="rbt-access-box">
                        <a href="{{ route('shop.index') }}" @class(['rbt-round-btn has-rbt-md-fsize', 'is-active' => request()->routeIs('shop.index', 'categories.show', 'brands.show', 'collections.show', 'products.show', 'market.show')])>
                            <i class="fa-regular fa-bag-shopping"></i>
                            <span class="rbt-toolbar-label"> Boutique</span>
                        </a>
                    </li>

                    @if (config('storefront.features.wishlist'))
                        <li class="rbt-access-box rbt-wishlist">
                            <a @class(['rbt-round-btn has-rbt-md-fsize', 'is-active' => request()->routeIs('wishlist.index', 'account.wishlist')]) href="{{ auth()->check() ? route('account.wishlist') : route('wishlist.index') }}" aria-label="Mes favoris ({{ $wishlistCount }})">
                                <i class="fa-regular fa-heart"></i>
                                <div class="access-box-count" data-wishlist-count @if (! $wishlistCount) hidden @endif>{{ $wishlistCount }}</div>
                                <span class="rbt-toolbar-label"> Favoris</span>
                            </a>
                        </li>
                    @endif

                    <li class="rbt-access-box">
                        <a @class(['rbt-common-search-trigger-active rbt-round-btn has-rbt-md-fsize rbt-modern-close-btn', 'is-active' => request()->routeIs('home')]) href="{{ route('home') }}">
                            <i class="fa-regular fa-house search-icon"></i>
                            <div class="modern-close-wrapper"></div>
                            <span class="rbt-toolbar-label"> Accueil</span>
                        </a>
                    </li>

                    @if (config('storefront.features.compare'))
                        <li class="rbt-access-box">
                            <a href="{{ route('compare.index') }}" @class(['rbt-round-btn has-rbt-md-fsize', 'is-active' => request()->routeIs('compare.index')]) aria-label="Comparateur ({{ $compared->count() }})">
                                <i class="fa-regular fa-code-compare"></i>
                                <div class="access-box-count" data-compare-count @if ($compared->isEmpty()) hidden @endif>{{ $compared->count() }}</div>
                                <span class="rbt-toolbar-label"> Comparer</span>
                            </a>
                        </li>
                    @else
                        <li class="rbt-access-box">
                            <a href="{{ route('cart.show') }}" @class(['rbt-round-btn has-rbt-md-fsize', 'is-active' => request()->routeIs('cart.show', 'checkout.*')])>
                                <i class="fa-regular fa-cart-shopping"></i>
                                <span class="rbt-toolbar-label"> Panier</span>
                            </a>
                        </li>
                    @endif

                    <li class="rbt-access-box">
                        @auth
                            <a href="{{ route('account.show') }}" @class(['rbt-round-btn has-rbt-md-fsize', 'is-active' => request()->routeIs('account.*')])>
                                <i class="fa-regular fa-user"></i>
                                <span class="rbt-toolbar-label"> Mon compte</span>
                            </a>
                        @else
                            <a href="#!" class="rbt-round-btn has-rbt-md-fsize" data-bs-toggle="modal" data-bs-target="#signinModal">
                                <i class="fa-regular fa-user"></i>
                                <span class="rbt-toolbar-label"> Profil</span>
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
