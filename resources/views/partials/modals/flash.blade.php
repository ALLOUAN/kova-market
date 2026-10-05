{{--
    Result of the last action (saved, added to the cart, form errors…). The messages stay in the page, marked
    data-popup; assets/js/flash.js moves them into this window on load, so they are still read without JavaScript.
--}}
<x-modal id="kovaFlashModal" dialog-class="modal-dialog-centered xxs-size" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="kova-flash" data-flash>
            <span class="kova-flash__icon" aria-hidden="true"><i class="fa-solid fa-circle-info" data-flash-icon></i></span>
            <h2 class="kova-flash__title h5" id="kovaFlashModalLabel" data-flash-title>Information</h2>
            <div class="kova-flash__message" data-flash-message></div>
            <button type="button" class="rbt-btn rbt-btn-sm kova-flash__close" data-bs-dismiss="modal">OK</button>
        </div>
    </div>
</x-modal>
