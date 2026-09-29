<div class="rbt-offcanvas-cat-side-menu rbt-category-sidemenu ">
    <div class="inner-wrapper">
        <div class="rbt-categories-sidebar d-flex">
            <div class="rbt-sidebar-left-content">
                <div class="rbt-sidebar-left-inner">
                    <div class="rbt-sidebar-left-content-head">
                        <div class="rbt-categories-sidebar-top-content mb--24">
                            @include('partials.header.logo')
                            <button class="rbt-sidebar-close-btn" aria-label="Fermer">
                                <i class="fa-sharp fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="rbt-access-box rbt-scroll-trigger fade_in animation-order-1 rbt-access-box-has-bg-hover rbt-access-box-has-bg-hover-white d-inline-block">
                            @auth
                                <a href="{{ route('account.show') }}" class="rbt-access-box-wrapper">
                                    <div class="rbt-round-btn rbt-bg-color-brand-300 rbt-text-color-primary has-rbt-sm-fsize">
                                        <i class="fa-regular fa-user"></i>
                                    </div>
                                    <div class="content">
                                        <p>Bonjour, {{ Str::before(auth()->user()->name, ' ') }}</p>
                                        <span>Mon compte</span>
                                    </div>
                                </a>
                            @else
                                <a href="#!" class="rbt-access-box-wrapper" data-bs-toggle="modal" data-bs-target="#signinModal">
                                    <div class="rbt-round-btn rbt-bg-color-brand-300 rbt-text-color-primary has-rbt-sm-fsize">
                                        <i class="fa-regular fa-user"></i>
                                    </div>
                                    <div class="content">
                                        <p>Connexion / Inscription</p>
                                        <span>Accéder au compte</span>
                                    </div>
                                </a>
                            @endauth
                        </div>
                    </div>

                    <div class="rbt-sidebar-tabs-wrapper">
                        <div class="rbt-sidebar-tabs-inner">
                            <ul class="rbt-sidebar-sub-categories nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                @foreach ($categoryTree as $category)
                                    <li>
                                        <button class="rbt-nav-link nav-link" id="rbt-tab-cat-sidebar-{{ $loop->iteration }}" data-bs-toggle="pill" data-bs-target="#rbt-nav-pill-{{ $loop->iteration }}" type="button" role="tab" aria-controls="rbt-nav-pill-{{ $loop->iteration }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            <span class="rbt-round-btn">
                                                <i class="{{ $category->icon }}"></i>
                                            </span>
                                            <span class="rbt-content">
                                                <span class="rbt-sub-category-title">
                                                    <span>{{ $category->name }}</span>
                                                    @if ($category->badge_label)
                                                        <span class="rbt-product-badge rbt-product-badge-bg-{{ $category->badge_variant }}">{{ $category->badge_label }}</span>
                                                    @endif
                                                </span>
                                                <span class="description">{{ $category->tagline }}</span>
                                            </span>
                                            <span class="icon">
                                                <i class="fa-regular fa-chevron-right"></i>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="rbt-sidebar-quick-links-part">
                                <div class="rbt-sidebar-bottom-inner">
                                    @foreach ($sidebarLinks as $title => $links)
                                        <hr @class(['rbt-separator rbt-separator-gray200 mb--24', 'mt--24' => ! $loop->first])>
                                        <nav class="rbt-sidebar-nav">
                                            <h2 class="rbt-sub-category-title h4">
                                                <a data-bs-toggle="collapse" href="#sidebar-links-{{ $loop->iteration }}" role="button" aria-expanded="false" aria-controls="sidebar-links-{{ $loop->iteration }}">
                                                    {{ $title }}
                                                    <span class="icon"><i class="fa-regular fa-chevron-down"></i></span>
                                                </a>
                                            </h2>
                                            <div class="collapse" id="sidebar-links-{{ $loop->iteration }}">
                                                <ul class="rbt-sidebar-quick-links">
                                                    @foreach ($links as $link)
                                                        <li><a href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </nav>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rbt-sidebar-left-content-footer">
                        <div class="rbt-sidebar-contact-area">
                            <div class="rbt-sidebar-contact-inner rbt-link-hover">
                                <p class="rbt-contact-text">{{ $contact['address'] }}</p>
                                <a class="rbt-contact-links" href="{{ $contact['phone_href'] }}">{{ $contact['phone'] }}</a>
                                <p class="rbt-contact-text mt--12">{{ $contact['opening_hours'] }}</p>
                                <a class="rbt-contact-links" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                                @if (filled($contact['address']))
                                    <a class="rbt-contact-links d-block" href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($contact['address']) }}" target="_blank" rel="noopener">Voir sur la carte</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rbt-sidebar-right-content">
                <div class="rbt-sidebar-right-inner">
                    <div class="tab-content" id="v-pills-tabContent">
                        @foreach ($categoryTree as $category)
                            <div @class(['rbt-tab-content tab-pane fade', 'show active' => $loop->first]) id="rbt-nav-pill-{{ $loop->iteration }}" role="tabpanel" aria-labelledby="rbt-tab-cat-sidebar-{{ $loop->iteration }}" tabindex="0">
                                <div class="rbt-sub-category-products">
                                    <div class="rbt-category-products-inner">
                                        @foreach ($category->children as $child)
                                            <div class="rbt-sub-category-product">
                                                <a href="{{ $child->url() }}" class="rbt-sidebar-category-img">
                                                    <img src="{{ asset($child->image ?? $category->image) }}" alt="{{ $child->name }}" loading="lazy" decoding="async">
                                                </a>
                                                <h2 class="rbt-category-offcanvas-header h5"><a href="{{ $child->url() }}">{{ $child->name }}</a></h2>
                                                <ul class="rbt-product-features has-link-underline-effect">
                                                    @forelse ($child->children as $leaf)
                                                        <li><a href="{{ $leaf->url() }}">{{ $leaf->name }}</a></li>
                                                    @empty
                                                        <li><a href="{{ $child->url() }}">Tout voir</a></li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($category->promo)
                                        <div class="rbt-sidebar-banner">
                                            <div class="rbt-banner-img">
                                                <img src="{{ asset($category->promo['image']) }}" alt="{{ $category->promo['title'] }}" loading="lazy" decoding="async">
                                            </div>
                                            <div class="rbt-sidebar-banner-content">
                                                <p class="rbt-sidebar-banner-text">{{ $category->promo['label'] }}
                                                    <span class="rbt-text-color-primary rbt-text-semi-bold ml--4">{{ $category->promo['highlight'] }}</span>
                                                </p>
                                                <h2 class="rbt-sidebar-banner-titile h4">{{ $category->promo['title'] }} <span class="rbt-text-regular">{{ $category->promo['subtitle'] }}</span>
                                                </h2>
                                                <a href="{{ $category->url() }}" class="rbt-btn rbt-btn-sm">En savoir plus</a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
