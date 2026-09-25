<div class="rbt-special-offprds-side-menu rbt-special-offer-sidemenu">
    <div class="inner-wrapper p--0">
        <aside class="rbt-sidebar">
            <div class="rbt-sidebar-widget-wrapper rbt-sidebar-bg-one">

                <div class="rbt-sidebar-top sticky-top-0 rbt-bg-color-white">
                    <h3 class="rbt-sidebar-title mb--0 h-auto"><i class="fa-sharp fa-regular fa-filter-list mr--4"></i>
                        Special Offers
                    </h3>

                    <button class="rbt-sidebar-close-btn" id="btn_filtersideNavClose" aria-label="Close">
                        <i class="fa-sharp fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="rbt-sidebar-bottom border-0">
                    <div class="row row--12 mt_dec--24">
                        @foreach ($promotions as $promotion)
                            <div class="col-12 mt--24">
                                <x-promotion.card :promotion="$promotion" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </aside>
    </div>
</div>
