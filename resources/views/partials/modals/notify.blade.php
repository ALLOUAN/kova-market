{{-- Back-in-stock alert from a sold-out product card (EX-17); storefront.js fills in the product. --}}
<x-modal id="notifyModal" dialog-class="xxs-size modal-dialog-centered" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-bg-color-white rbt-content-trs-portion">
            <div class="rbt-notify-modal-content">
                <div class="rbt-title rbt-text-bold mb--8 h5" id="notifyModalLabel">
                    <span class="mr--4"><i class="fa-regular fa-bell"></i></span>
                    Alerte de retour en stock
                </div>
                <div class="rbt-info-wrapper d-flex mt--8 rbt-gap--12">
                    <div class="rbt-info-box rbt-notify-box w-100">
                        <p class="b1 mb--16">
                            Voulez-vous être prévenu du retour en stock de <strong data-stock-alert-name>ce produit</strong> ?
                        </p>
                        <x-product.stock-alert-form prefix="notify" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-modal>
