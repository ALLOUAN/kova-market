{{-- Tabbed "Shop" mega menu: one tab per department, sub-categories in columns and the department promo. --}}
<div class="rbt-megamenu rbt-megamenu-4">
    <div class="rbt-megamenu-wrapper p--0">
        <div class="row row--0">
            <div class="col-3 ">
                <div class="rbt-menu-tab-wrapper">
                    <nav id="rbt-megamenuTab{{ $idSuffix }}" class="nav nav-pills flex-column rbt-megamenu-tab rbt-megamenu-tab-cs-activation">
                        @foreach ($categoryTree as $category)
                            <a href="#rbt-megamenu_tab{{ $loop->iteration }}{{ $idSuffix }}" data-bs-toggle="pill" @class(['nav-link', 'active' => $loop->first])>
                                <span><i class="{{ $category->icon }}"></i></span>
                                {{ $category->name }}
                                <span class="rbt-chevron-right"><i class="fa-regular fa-chevron-right"></i></span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>

            <div class="col-9">
                <div class="rbt-menu-tab-content-wrapper">
                    <div class="tab-content rbt-megamenu-tab-content" id="megamenu-tab-content{{ $idSuffix }}">
                        @foreach ($categoryTree as $category)
                            <div @class(['tab-pane', 'show active' => $loop->first, 'fade' => ! $loop->first]) id="rbt-megamenu_tab{{ $loop->iteration }}{{ $idSuffix }}">
                                <div class="row row--24">
                                    <div class="col-xl-8">
                                        <div class="row row--8">
                                            @foreach ($category->children->chunk((int) ceil($category->children->count() / 3)) as $column)
                                                <div class="col-xl-4 single-mega-item rbt-scroll-trigger fade_in animation-order-{{ $loop->iteration }}">
                                                    <p class="rbt-short-title h5">{{ $loop->first ? $category->name : ($loop->last ? 'Et aussi' : 'Populaires') }}</p>
                                                    <ul class="mega-menu-item">
                                                        @foreach ($column as $child)
                                                            <li><a href="{{ $child->url() }}">{{ $child->name }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @if ($category->promo)
                                        <div class="col-xl-4 rbt-scroll-trigger fade_in animation-order-4">
                                            <div class="rbt-menu-offer-card rbt-bg-style-box rbt-bg-three h-100 min-h-500">
                                                <div class="mega-top-banner h-100 align-items-start justify-content-center">
                                                    <div class="rbt-banner-inner rbt-banner-inner-black flex-column rbt-gap--16 align-items-center text-center">
                                                        <div class="rbt-banner-content">
                                                            <p class="b4 subtitle mb--0">{{ $category->promo['label'] }} {{ $category->promo['highlight'] }}</p>
                                                            <{{ $headingTag }} class="h5 mb--4">{{ $category->promo['title'] }}
                                                                {{ $category->promo['subtitle'] }}</{{ $headingTag }}>
                                                        </div>
                                                        <a class="rbt-btn rbt-bg-color-secondary rbt-btn-sm" href="{{ $category->url() }}">Voir la collection</a>
                                                    </div>
                                                </div>
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
