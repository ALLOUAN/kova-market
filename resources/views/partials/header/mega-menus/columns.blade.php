{{-- Full width mega menu: link columns from config/navigation.php and the brand logos strip. --}}
<div class="rbt-megamenu rbt-width-fullscreen p-0 ">
    <div class="rbt-megamenu-wrapper">
        <div class="wrapper">
            <div class="row row--12 mt_dec--12">
                <div class="col-xl-9">
                    <div class="h-100 d-flex flex-column justify-content-between">
                        <div class="row">
                            @foreach ($columns as $column)
                                <div class="col-12 col-lg-1-5 single-mega-item rbt-scroll-trigger fade_in animation-order-1 mt--16">
                                    <p class="rbt-short-title h5">{{ $column['title'] }}</p>
                                    <ul class="mega-menu-item">
                                        @foreach ($column['links'] as $link)
                                            <li @class(['active' => $link['active']])>
                                                <a href="{{ $link['href'] }}" @if ($link['active']) aria-current="page" @endif>
                                                    {{ $link['label'] }}
                                                    @isset($link['badge'])
                                                        <div class="rbt-product-badge rbt-product-badge-bg-{{ $link['badge']['variant'] }} border-rounded">{{ $link['badge']['label'] }}</div>
                                                    @endisset
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        @if ($navBrands->isNotEmpty())
                            <div class="row">
                                <div class="col-12">
                                    <hr class="rbt-separator rbt-separator-gray200 mb--16 mt--16 mt_sm--12 mb_sm--12 rbt-bg-color-gray-100">
                                </div>
                                <div class="col-lg-12">
                                    <ul class="rbt-nav-brand-list liststyle d-flex justify-content-xl-between">
                                        @foreach ($navBrands as $brand)
                                            <li><a href="{{ $brand->url() }}"><img src="{{ asset($brand->logo) }}" alt="{{ $brand->name }}"></a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
