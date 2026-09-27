<x-modal id="signinModal" dialog-class="rbt-register-form-modal modal-dialog-centered" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-login-form rbt-bg-color-white rbt-content-trs-portion">
            <div class="rbt-login-form-inner">
                <div class="rbt-login-form-top">
                    <div class="logo">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset('assets/images/logo/logo.webp') }}" alt="Logo">
                        </a>
                    </div>
                    <h3 class="rbt-title rbt-text-bold mb--16 h6" id="signinModalLabel">Se connecter pour continuer</h3>
                    {{-- Customers log in with their phone number or their e-mail address (F-070). --}}
                    <form method="POST" action="{{ route('login.store') }}" novalidate>
                        @csrf
                        <input type="hidden" name="_form" value="signin">

                        <div class="rbt-input-field-grp">
                            <label class="rbt-field-label" for="modal_signin_login">Téléphone ou e-mail<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" placeholder="07 01 02 03 04 ou vous@exemple.ci" type="text" id="modal_signin_login" name="login" value="{{ old('_form') === 'signin' ? old('login') : '' }}" autocomplete="username" required>
                            @if (old('_form') === 'signin')
                                @error('login')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="rbt-input-field-grp mt--16">
                            <label class="rbt-field-label" for="modal_signin_password">Mot de passe<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" type="password" id="modal_signin_password" name="password" autocomplete="current-password" required>
                        </div>

                        <button type="submit" class="rbt-btn d-block w-100 mt--24 mb--16">
                            Se connecter
                        </button>
                        <div class="rbt-check-group">
                            <input id="modal_login_checked1" type="checkbox" name="remember" value="1">
                            <label for="modal_login_checked1">Rester connecté</label>
                        </div>
                    </form>

                    <div class="rbt-login-system-switch rbt-link-hover">
                        Pas encore de compte ?
                        <button class="rbt-switch-btn" data-bs-toggle="modal" data-bs-target="#signupModal" data-bs-dismiss="modal" aria-label="Créer un compte"><span>Créer un compte</span></button>
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
