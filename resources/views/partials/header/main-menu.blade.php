{{--
    Main navigation, rendered by the header, the sticky header and the mobile menu.
    $idSuffix keeps element ids unique between copies; $headingTag avoids duplicate headings;
    $mobile renders the category mega menu as a simple dropdown.
--}}
@php
    $idSuffix ??= '';
    $headingTag ??= 'h2';
    $mobile ??= false;
@endphp
<nav class="rbt-mainmenu-nav">
    <ul @class(['mainmenu', 'has-nav-bg-shape-hover' => ! $mobile])>
        @foreach ($mainMenu as $item)
            @switch($item['type'] ?? 'link')
                @case('categories')
                    @if ($mobile)
                        <li class="has-dropdown position-relative">
                            <a href="#!">{{ $item['label'] }} <i class="fa-regular fa-chevron-down"></i></a>
                            <ul class="submenu">
                                @foreach ($categoryTree as $category)
                                    <li><a href="{{ $category->url() }}">{{ $category->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="with-rbt-megamenu has-menu-child-item position-static">
                            <a href="#!">{{ $item['label'] }} <i class="fa-regular fa-chevron-down"></i></a>
                            @include('partials.header.mega-menus.categories')
                        </li>
                    @endif
                    @break

                {{-- "Mon Marché": the same mega menu as "Boutique", with the market's categories. --}}
                @case('market')
                    @php
                        $market = app(\App\Services\Storefront\Market::class);
                        $marketTree = $market->menuTree();
                    @endphp
                    @if ($mobile)
                        <li class="has-dropdown position-relative kova-menu-highlight">
                            <a href="#!"><i class="{{ $item['icon'] }} mr--4"></i>{{ $item['label'] }} <i class="fa-regular fa-chevron-down"></i></a>
                            <ul class="submenu">
                                <li><a href="{{ $item['href'] }}">Tout {{ $item['label'] }}</a></li>
                                @foreach ($marketTree as $rayon)
                                    <li><a href="{{ $rayon->url() }}">{{ $rayon->name }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="with-rbt-megamenu has-menu-child-item position-static kova-menu-highlight">
                            <a href="{{ $item['href'] }}"><i class="{{ $item['icon'] }} mr--4"></i>{{ $item['label'] }} <i class="fa-regular fa-chevron-down"></i></a>
                            @include('partials.header.mega-menus.categories', ['categoryTree' => $marketTree, 'idSuffix' => $idSuffix.'-market', 'promoFallback' => $market->category()])
                        </li>
                    @endif
                    @break

                @case('mega')
                    <li class="with-rbt-megamenu has-menu-child-item position-static">
                        <a href="#!">{{ $item['label'] }} <i class="fa-regular fa-chevron-down"></i></a>
                        @include('partials.header.mega-menus.columns', ['columns' => $item['columns']])
                    </li>
                    @break

                @case('dropdown')
                    <li class="has-dropdown position-relative">
                        <a href="#!">{{ $item['label'] }} <i class="fa-regular fa-chevron-down"></i></a>
                        <ul class="submenu">
                            @foreach ($item['links'] as $link)
                                <li>
                                    <a href="{{ $link['href'] }}">
                                        {{ $link['label'] }}
                                        @isset($link['badge'])
                                            <div class="rbt-product-badge rbt-product-badge-bg-{{ $link['badge']['variant'] }} border-rounded">{{ $link['badge']['label'] }}</div>
                                        @endisset
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                    @break

                @default
                    <li @class(['position-relative', 'kova-menu-highlight' => $item['highlight'] ?? false])>
                        <a href="{{ $item['href'] }}">@isset($item['icon'])<i class="{{ $item['icon'] }} mr--4"></i>@endisset{{ $item['label'] }}</a>
                    </li>
            @endswitch
        @endforeach
    </ul>
</nav>
