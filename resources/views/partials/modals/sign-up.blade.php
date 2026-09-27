<x-modal id="signupModal" dialog-class="rbt-register-form-modal modal-dialog-centered" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-login-form rbt-bg-color-white rbt-content-trs-portion">
            <div class="rbt-login-form-inner">
                <div class="rbt-login-form-top">
                    <div class="logo">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset('assets/images/logo/logo.webp') }}" alt="Logo">
                        </a>
                    </div>
                    <h3 class="rbt-title rbt-text-bold mb--16 h6" id="signupModalLabel">Créer un compte</h3>
                    <p class="description">Je souhaite être livré à :</p>
                    <ul class="rbt-signup-radio-list">
                        <li class="rbt-check-grp ml--0">
                            <input id="modal-rbt-signup-radio-1" type="radio" name="modal-rbt-signup-radio">
                            <label for="modal-rbt-signup-radio-1">
                                <span class="rbt-lable-text">Accueil</span>
                            </label>
                        </li>
                        <li class="rbt-check-grp ml--0">
                            <input id="modal-rbt-signup-radio-2" type="radio" name="modal-rbt-signup-radio">
                            <label for="modal-rbt-signup-radio-2">
                                <span class="rbt-lable-text">Bureau</span>
                            </label>
                        </li>
                        <li class="rbt-check-grp ml--0">
                            <input id="modal-rbt-signup-radio-3" type="radio" name="modal-rbt-signup-radio">
                            <label for="modal-rbt-signup-radio-3">
                                <span class="rbt-lable-text">Professionnels</span>
                            </label>
                        </li>
                        <li class="rbt-check-grp ml--0">
                            <input id="modal-rbt-signup-radio-4" type="radio" name="modal-rbt-signup-radio">
                            <label for="modal-rbt-signup-radio-4">
                                <span class="rbt-lable-text">Autres</span>
                            </label>
                        </li>
                    </ul>
                    <div class="rbt-tab rbt-round-shape-tab">

                        <ul class="nav nav-tabs" id="modal_signinTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="rbt-form-tab-id-3" data-bs-toggle="tab" data-bs-target="#rbt-form-tab-pane-3" type="button" role="tab" aria-controls="rbt-form-tab-pane-3" aria-selected="true">
                                    <i class="fa-sharp fa-regular fa-phone"></i>
                                    Numéro de téléphone
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="rbt-form-tab-id-4" data-bs-toggle="tab" data-bs-target="#rbt-form-tab-pane-4" type="button" role="tab" aria-controls="rbt-form-tab-pane-4" aria-selected="false">
                                    <i class="fa-sharp fa-regular fa-envelope"></i>
                                    E-mail
                                </button>
                            </li>
                        </ul>

                        <form>
                            <div class="tab-content" id="modal_signinTabContent">
                                <div class="tab-pane fade show active" id="rbt-form-tab-pane-3" role="tabpanel" aria-labelledby="rbt-form-tab-id-3" tabindex="0">
                                    <div class="rbt-input-field-grp">
                                        <label class="rbt-field-label" for="modal_register_number">Votre numéro<span class="rbt-text-color-danger">*</span></label>
                                        <input class="rbt-input-field" placeholder="Numéro" type="text" id="modal_register_number">
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="rbt-form-tab-pane-4" role="tabpanel" aria-labelledby="rbt-form-tab-id-4" tabindex="0">
                                    <div class="rbt-input-field-grp">
                                        <label class="rbt-field-label" for="modal_register_email">Votre e-mail<span class="rbt-text-color-danger">*</span></label>
                                        <input class="rbt-input-field" placeholder="E-mail" type="email" id="modal_register_email">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="rbt-btn d-block w-100 mt--24 mb--16">
                                Continuer
                            </button>
                            <div class="rbt-check-group">
                                <input id="modal_signup_login_checked2" type="checkbox" name="login">
                                <label for="modal_signup_login_checked2">Rester connecté</label>
                            </div>
                        </form>

                    </div>

                    <div class="d-flex align-items-center justify-content-center mb--24 mt--24">
                        <hr class="rbt-separator rbt-bg-color-gray-light mb--0">
                        <span class="pl--8 pr--8 b4 rbt-text-medium">OU</span>
                        <hr class="rbt-separator rbt-bg-color-gray-light mb--0">
                    </div>

                    <button type="submit" class="rbt-btn rbt-btn-border rbt-social-login-btn d-block w-100 mb--16 rbt-social-login-btn">
                        <img class="icon" src="{{ asset('assets/images/icons/fb-icon.webp') }}" alt="Icône">
                        Continuer avec Facebook
                    </button>
                    <button type="submit" class="rbt-btn rbt-btn-border rbt-social-login-btn d-block w-100 rbt-social-login-btn">
                        <img class="icon" src="{{ asset('assets/images/icons/google-icon.webp') }}" alt="Icône">
                        Continuer avec Google
                    </button>

                    <div class="rbt-login-system-switch rbt-link-hover">
                        Déjà client ?
                        <button class="rbt-switch-btn" data-bs-toggle="modal" data-bs-target="#signinModal" data-bs-dismiss="modal" aria-label="Fermer"><span>Se connecter</span></button>
                    </div>
                </div>

                <div class="rbt-login-form-bottom rbt-swiper-container-pagination position-relative">
                    <div class="swiper rbt-log-slide-activation pb--40">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="rbt-client-review">
                                    <ul class="rbt-rating-icon-list d-flex justify-content-center">
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                    </ul>
                                    <p class="rbt-review-text mt--8 mb--12">
                                        "Produit conforme, très bonne qualité. Livraison rapide,
                                        je recommande
                                        KOVA MARKET."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Client vérifié
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="rbt-client-review">
                                    <ul class="rbt-rating-icon-list d-flex justify-content-center">
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                    </ul>
                                    <p class="rbt-review-text mt--8 mb--12">
                                        "Produit conforme, très bonne qualité. Livraison rapide,
                                        je recommande
                                        KOVA MARKET."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Client vérifié
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="rbt-client-review">
                                    <ul class="rbt-rating-icon-list d-flex justify-content-center">
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                    </ul>
                                    <p class="rbt-review-text mt--8 mb--12">
                                        "Produit conforme, très bonne qualité. Livraison rapide,
                                        je recommande
                                        KOVA MARKET."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Client vérifié
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="rbt-client-review">
                                    <ul class="rbt-rating-icon-list d-flex justify-content-center">
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                        <li><i class="fa-solid fa-star rbt-rated-icon"></i></li>
                                    </ul>
                                    <p class="rbt-review-text mt--8 mb--12">
                                        "Produit conforme, très bonne qualité. Livraison rapide,
                                        je recommande
                                        KOVA MARKET."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Client vérifié
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-pagination rbt-swiper-progress rbt-swiper-pagination-dot-extend">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-modal>
