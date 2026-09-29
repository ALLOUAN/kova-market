<div class="popup-mobile-menu">
    <div class="inner-wrapper">
        <div class="mobile-menu-top">
            <div class="inner-top">
                <div class="content">
                    @include('partials.header.logo')
                    <div class="rbt-btn-close">
                        <button class="close-button rbt-round-btn" aria-label="Fermer le menu"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>
                <p class="description">{{ config('storefront.description') }}</p>
                <form action="{{ route('shop.index') }}" method="GET" role="search" class="rbt-inner-search-field style-one rbt-search-field-rounded rbt-search-field-sm-width">
                    <input type="text" name="q" placeholder="Rechercher un produit" aria-label="Rechercher">
                    <button class="rbt-round-btn search-btn rbt-text-color-gray-500" type="submit" aria-label="Rechercher"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
            <div class="rbt-tab rbt-round-shape-tab">
                <ul class="nav nav-tabs mb--0" id="mobile-menuTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="rbt-tab-mobilemenu-1" data-bs-toggle="tab" data-bs-target="#rbt-tab-pane-mobilemenu-1" type="button" role="tab" aria-controls="rbt-tab-pane-mobilemenu-1" aria-selected="true">
                            <i class="fa-solid fa-bars-sort"></i>
                            Menu
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rbt-tab-mobilemenu-2" data-bs-toggle="tab" data-bs-target="#rbt-tab-pane-mobilemenu-2" type="button" role="tab" aria-controls="rbt-tab-pane-mobilemenu-2" aria-selected="false">
                            <i class="fa-sharp fa-regular fa-layer-group"></i>
                            Catégories
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="mobile-menuTabContent">
                    <div class="tab-pane fade show active" id="rbt-tab-pane-mobilemenu-1" role="tabpanel" aria-labelledby="rbt-tab-mobilemenu-1" tabindex="0">
                        @include('partials.header.main-menu', ['idSuffix' => '-mobile', 'headingTag' => 'p', 'mobile' => true])
                    </div>
                    <div class="tab-pane fade" id="rbt-tab-pane-mobilemenu-2" role="tabpanel" aria-labelledby="rbt-tab-mobilemenu-2" tabindex="0">
                        <nav class="rbt-mainmenu-nav">
                            <ul class="mainmenu">
                                @foreach ($categoryTree as $category)
                                    @if ($category->children->isEmpty())
                                        <li>
                                            <a href="{{ $category->url() }}">
                                                <span><i class="rbt-catagories-icon mr--8 {{ $category->icon }}"></i></span>{{ $category->name }}
                                            </a>
                                        </li>
                                    @else
                                        <li class="with-rbt-megamenu has-menu-child-item position-static">
                                            <a href="{{ $category->url() }}">
                                                <span><i class="rbt-catagories-icon mr--8 {{ $category->icon }}"></i></span>{{ $category->name }}
                                                <span class="rbt-chevron-right"><i class="fa-regular fa-chevron-right"></i></span>
                                            </a>
                                            <div class="rbt-megamenu grid-item-5 pl_sm--0 pl_md--0 pl_lg--0">
                                                <div class="container p_sm--0 p_md--0 p_lg--0">
                                                    <div class="rbt-megamenu-wrapper">
                                                        <div class="row row--12">
                                                            @foreach ($category->children->chunk((int) ceil($category->children->count() / 2)) as $column)
                                                                <div class="col-lg-12 col-xl-3 col-xxl-3 single-mega-item rbt-scroll-trigger fade_in animation-order-1">
                                                                    <p class="rbt-short-title h5">{{ $loop->first ? $category->name : 'Tout '.$category->name }}</p>
                                                                    <ul class="mega-menu-item">
                                                                        @foreach ($column as $child)
                                                                            <li><a href="{{ $child->url() }}">{{ $child->name }}</a></li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endforeach
                                                            @if ($category->promo && $category->image)
                                                                <div class="col-lg-12 col-xl-3 col-xxl-3 single-mega-item rbt-scroll-trigger fade_in animation-order-1">
                                                                    <div class="rbt-menu-offer-card rbt-bg-color-brand-50 rbt-rounded--12">
                                                                        <div class="mega-top-banner">
                                                                            <div class="rbt-banner-inner flex-column justify-content-center rbt-gap--8 align-items-center text-center">
                                                                                <div class="rbt-banner-content">
                                                                                    <h2 class="title">{{ $category->promo['title'] }}</h2>
                                                                                    <p class="b3 desc">{{ $category->promo['subtitle'] }}</p>
                                                                                </div>
                                                                                <a class="rbt-btn rbt-btn-sm rbt-btn-black" href="{{ $category->url() }}">Voir le détail</a>
                                                                                <a href="{{ $category->url() }}" class="product-img position-bottom mt--24"><img src="{{ asset($category->image) }}" alt="{{ $category->name }}" loading="lazy" decoding="async"></a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                                <li>
                                    <a href="#">
                                        Toutes les catégories
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="mobile-menu-bottom">
            @if ($socialLinks->isNotEmpty())
            <div class="social-share-wrapper">
                <span class="rbt-short-title d-block">Suivez-nous</span>
                <ul class="rbt-social-icon-list mt--12">
                    @foreach ($socialLinks as $network)
                        <li><a href="{{ $network['url'] }}" aria-label="{{ $network['icon'] }}"><i class="fa-brands {{ $network['icon'] }}"></i></a></li>
                    @endforeach
                </ul>
            </div>
            @endif
            <ul class="navbar-top-left rbt-information-list justify-content-center">
                <li>
                    <a href="mailto:{{ $contact['email'] }}"><i class="fa-light fa-envelope"></i>{{ $contact['email'] }}</a>
                </li>
                <li>
                    <a href="{{ $contact['phone_href'] }}"><i class="fa-regular fa-phone"></i>{{ $contact['phone'] }}</a>
                </li>
            </ul>
        </div>
    </div>
</div>
