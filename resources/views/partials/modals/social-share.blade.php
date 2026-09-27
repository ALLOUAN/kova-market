<x-modal id="socialShareModal" dialog-class="modal-dialog-centered xxs-size" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-bg-color-white rbt-content-trs-portion">
            <div class="rbt-title mb--8 rbt-text-bold" id="socialShareModalLabel">Options de partage</div>
            <div class="rbt-social-share-wrapper">

                <ul class="social-icon rbt-social-default mt--16 mt_sm--0 rbt-social-default-v1 lg-size justify-content-start">
                    <li>
                        <a class="facebook-btn" href="https://www.facebook.com">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                    </li>
                    <li>
                        <a class="tiktok-btn" href="https://www.tiktok.com">
                            <i class="fa-brands fa-tiktok"></i>
                        </a>
                    </li>
                    <li>
                        <a class="pinterest-btn" href="https://www.pinterest.com">
                            <i class="fa-brands fa-pinterest-p"></i>
                        </a>
                    </li>
                    <li>
                        <a class="tumblr-btn" href="https://www.tumblr.com/">
                            <i class="fa-brands fa-tumblr"></i>
                        </a>
                    </li>
                    <li>
                        <a class="telegram-btn" href="https://www.telegram.com">
                            <i class="fa-brands fa-telegram"></i>
                        </a>
                    </li>
                    <li>
                        <a class="whatsapp-btn" href="https://www.whatsapp.com/">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </li>
                    <li>
                        <a class="email-btn" href="mailto:{{ $contact['email'] }}">
                            <i class="fa-regular fa-envelope"></i>
                        </a>
                    </li>
                </ul>

                <div class="rbt-copy-link-part rbt-text-copy-activation mt--24 mt_sm--8 w-100">
                    <input class="rbt-copy-value-field w-100" type="text" value="{{ url('/') }}/wishlist" readonly="">
                    <button class="rbt-btn rbt-btn-xs has-left-icon rbt-copy-btn" data-tooltip="Copier dans le presse-papiers">
                        <i class="fa-regular fa-copy"></i>
                        <span class="rbt-btn-text">Copier</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-modal>
