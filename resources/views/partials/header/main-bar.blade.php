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
                            <a href="#" class="rbt-access-box-wrapper" data-logout>
                                <div class="rbt-round-btn rbt-bg-static-gray">
                                    <i class="fa-regular fa-user"></i>
                                </div>
                                <div class="content">
                                    <p>Bonjour, {{ Str::before(auth()->user()->name, ' ') }}</p>
                                    <span>Se déconnecter</span>
                                </div>
                            </a>
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
                    <li class="rbt-access-box rbt-scroll-trigger fade_in animation-order-3 rbt-access-box-has-bg-hover rbt-mini-cart">
                        <a href="#" class="rbt-access-box-wrapper rbt-cart-sidenav-activation">
                            <div class="rbt-round-btn rbt-bg-static-gray">
                                <i class="fa-regular fa-bag-shopping"></i>
                                <span class="access-box-count rbt-shiny">{{ $cartSummary->count() }}</span>
                            </div>
                            <div class="content p-0">
                                <p>Total du panier</p>
                                <span>@money($cartSummary->total())</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
