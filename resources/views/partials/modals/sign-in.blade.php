<x-modal id="signinModal" dialog-class="rbt-register-form-modal modal-dialog-centered" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-login-form rbt-bg-color-white rbt-content-trs-portion">
            <div class="rbt-login-form-inner">
                <div class="rbt-login-form-top">
                    <div class="logo">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset('assets/images/logo/logo.webp') }}" alt="Ecommerce Logo Images">
                        </a>
                    </div>
                    <h3 class="rbt-title rbt-text-bold mb--16 h6" id="signinModalLabel">Sign In To Proceed</h3>
                    <div class="rbt-tab rbt-round-shape-tab">

                        <ul class="nav nav-tabs" id="registerFormTab1" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="rbt-form-tab-id-1" data-bs-toggle="tab" data-bs-target="#rbt-form-tab-pane-1" type="button" role="tab" aria-controls="rbt-form-tab-pane-1" aria-selected="true">
                                    <i class="fa-sharp fa-regular fa-phone"></i>
                                    Phone Number
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="rbt-form-tab-id-2" data-bs-toggle="tab" data-bs-target="#rbt-form-tab-pane-2" type="button" role="tab" aria-controls="rbt-form-tab-pane-2" aria-selected="false">
                                    <i class="fa-sharp fa-regular fa-envelope"></i>
                                    Email
                                </button>
                            </li>
                        </ul>

                        <form>

                            <div class="tab-content" id="registerFormTab1Content">
                                <div class="tab-pane fade show active" id="rbt-form-tab-pane-1" role="tabpanel" aria-labelledby="rbt-form-tab-id-1" tabindex="0">
                                    <div class="rbt-input-field-grp">
                                        <label class="rbt-field-label" for="modal_signin_number">Your
                                            Number<span class="rbt-text-color-danger">*</span></label>
                                        <input class="rbt-input-field" placeholder="Number" type="text" id="modal_signin_number">
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="rbt-form-tab-pane-2" role="tabpanel" aria-labelledby="rbt-form-tab-id-2" tabindex="0">
                                    <div class="rbt-input-field-grp">
                                        <label class="rbt-field-label" for="modal_signin_email">Your Email<span class="rbt-text-color-danger">*</span></label>
                                        <input class="rbt-input-field" placeholder="Email" type="email" id="modal_signin_email">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="rbt-btn d-block w-100 mt--24 mb--16">
                                Continue
                            </button>
                            <div class="rbt-check-group">
                                <input id="modal_login_checked1" type="checkbox" name="login">
                                <label for="modal_login_checked1">Stay Logged In</label>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex align-items-center justify-content-center mb--24 mt--24">
                        <hr class="rbt-separator rbt-bg-color-gray-light mb--0">
                        <span class="pl--8 pr--8 b4 rbt-text-medium">OR</span>
                        <hr class="rbt-separator rbt-bg-color-gray-light mb--0">
                    </div>

                    <button type="submit" class="rbt-btn rbt-btn-border rbt-social-login-btn d-block w-100 mb--16 rbt-social-login-btn">
                        <img class="icon" src="{{ asset('assets/images/icons/fb-icon.webp') }}" alt="Icon">
                        Continue with Facebook
                    </button>
                    <button type="submit" class="rbt-btn rbt-btn-border rbt-social-login-btn d-block w-100 rbt-social-login-btn">
                        <img class="icon" src="{{ asset('assets/images/icons/google-icon.webp') }}" alt="Icon">
                        Continue with Google
                    </button>

                    <div class="rbt-login-system-switch rbt-link-hover">
                        Don't have an account?
                        <button class="rbt-switch-btn" data-bs-toggle="modal" data-bs-target="#signupModal" data-bs-dismiss="modal" aria-label="Close"><span>Create an account</span></button>
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
                                        "The shirt fits great, very good quality of the material. Training
                                        in it is pure
                                        pleasure."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Verified Reviewer
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
                                        "The shirt fits great, very good quality of the material. Training
                                        in it is pure
                                        pleasure."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Verified Reviewer
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
                                        "The shirt fits great, very good quality of the material. Training
                                        in it is pure
                                        pleasure."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Verified Reviewer
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
                                        "The shirt fits great, very good quality of the material. Training
                                        in it is pure
                                        pleasure."
                                    </p>
                                    <div class="d-flex flex-wrap justify-content-center rbt-gap--8">
                                        <h3 class="mb--0 h6">Szilagyi Erik</h3>
                                        <div class="rbt-verified-badge badge-rounded">
                                            <i class="fa-sharp fa-solid fa-shield-check"></i>
                                            Verified Reviewer
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
