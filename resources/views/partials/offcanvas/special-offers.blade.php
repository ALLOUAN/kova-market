<div class="rbt-special-offprds-side-menu rbt-special-offer-sidemenu">
    <div class="inner-wrapper p--0">
        <aside class="rbt-sidebar">
            <div class="rbt-sidebar-widget-wrapper rbt-sidebar-bg-one">

                <div class="rbt-sidebar-top sticky-top-0 rbt-bg-color-white">
                    <h3 class="rbt-sidebar-title mb--0 h-auto"><i class="fa-sharp fa-regular fa-filter-list mr--4"></i>
                        Offres spéciales
                    </h3>

                    <button class="rbt-sidebar-close-btn" id="btn_filtersideNavClose" aria-label="Fermer">
                        <i class="fa-sharp fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="rbt-sidebar-bottom border-0">
                    <div class="row row--12 mt_dec--24">
                        {{-- Cards of Promotions › Offres spéciales in the back-office, running or coming. --}}
                        @forelse ($promotions as $promotion)
                            <div class="col-12 mt--24">
                                <x-promotion.card :promotion="$promotion" />
                            </div>
                        @empty
                            <div class="col-12 mt--24 text-center">
                                <p class="mb--16">Aucune offre spéciale en ce moment. Revenez bientôt !</p>
                                <a class="rbt-btn rbt-btn-border rbt-btn-sm" style="width: auto; padding: 0 20px" href="{{ route('shop.index') }}">Voir la boutique</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </aside>
    </div>
</div>
