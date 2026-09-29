<div class="rbt-search-dropdown rbt-common-search-dropdown-activation">
    <div class="wrapper">
        <div class="row">
            <div class="col-lg-12">
                <div class="rbt-component-section-title border-0 p-0 text-center">
                    <h2 class="rbt-title text-start text-md-center"><span class="rbt-bold--text">Rechercher un produit</span></h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <form class="rbt-search-form" action="{{ route('shop.index') }}" method="GET" role="search">
                    <div class="input-sectition position-relative w-100 mr--12 mr_sm--4">
                        <input class="search-input" type="text" name="q" placeholder="Que recherchez-vous ?" aria-label="Rechercher">
                        <i class="fa-sharp fa-regular inner-search-icon fa-magnifying-glass"></i>
                    </div>
                    {{-- The template's image search (camera, file drop) has no back end: removed. --}}
                    <div class="submit-btn">
                        <button type="submit" class="rbt-btn btn-md">Rechercher</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="rbt-search-scroll-vertical-wrapper rbt-scroll-vertical">
            <div class="inner">
                <div class="row row--0">
                    <div class="col-lg-12">
                        <div class="border-0 p-0 text-left title-sm-fsize">
                            <h2 class="title"><span class="rbt-bold--text">Recherches populaires</span></h2>
                        </div>
                    </div>

                    <div class="rbt-search-list-wrapper rbt-tag-list rbt-tag-list-rounded-lg">
                        @foreach (config('storefront.search.popular') as $term)
                            <a href="{{ route('shop.index', ['q' => $term]) }}">{{ $term }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="rbt-separator-mid ptb--24">
                    <hr class="rbt-separator m-0">
                </div>

                <div class="row row--0">
                    <div class="col-lg-12">
                        <div class="border-0 p-0 text-left title-sm-fsize">
                            <h2 class="title"><span class="rbt-bold--text">Produits tendance</span></h2>
                        </div>
                    </div>
                </div>

                <div class="row row--12 m--0 mt_dec--24">
                    @foreach ($trendingProducts as $product)
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mt--24 mt_sm--16">
                            <x-product.card :product="$product" :order="$loop->iteration" heading="h2" />
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

    </div>
</div>
