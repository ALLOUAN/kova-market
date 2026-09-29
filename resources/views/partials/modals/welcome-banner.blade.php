<x-modal id="welcomebannerModal" dialog-class="modal-dialog-centered xs-size">
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-welcome-banner-area rbt-content-trs-portion">
            <div class="rbt-welcome-banner-content">
                <div class="rbt-welcome-banner-top position-relative overflow-hidden rbt-rounded--12">
                    <img src="{{ asset('assets/images/banner-img/welcome-banner-img-01.webp') }}" alt="Bannière" loading="lazy" decoding="async">
                </div>
                <div class="rbt-welcome-banner-bottom pt--32">
                    <h2 class="text-center mb--12">Ne manquez pas nos offres</h2>
                    <p class="text-center b1 mb--32">Soyez le premier à profiter des nouveautés à prix de lancement.</p>
                    <form id="rbtSubscribe-form" action="#" class="form-newsletter">
                        <div class="rbt-input-field-grp">
                            <input class="rbt-input-field" type="email" placeholder="Votre adresse e-mail *">
                        </div>
                        <button type="submit" class="rbt-btn d-block w-100 mt--24 mb--16 radius-round-6">
                            Me tenir informé
                        </button>
                    </form>
                    <div class="text-center">
                        <a href="#" data-bs-dismiss="modal" class="rbt-btn rbt-btn-naked radius-round-6 d-block">Pas intéressé</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-modal>
