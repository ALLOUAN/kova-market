<x-modal id="recent-viewModal" dialog-class="modal-dialog-centered xs-size">
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-recent-view-prd-area rbt-content-trs-portion rbt-scroll-vertical-wrapper">

            <h3 class="rbt-title mb--16 rbt-text-bold h6">Produits récemment consultés</h3>
            <div class="rbt-scroll-vertical">
                <div class="row row--12 mt_dec--24 rbt-card-row-has-top-separator rbt-two-align-card-row">
                    @foreach ($recentlyViewedProducts as $product)
                        <div class="col-lg-6 col-md-6 col-sm-6 col-12 mt--24">
                            <x-product.list-card :product="$product" heading="h3" :order="$loop->iteration" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-modal>
