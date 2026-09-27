@php($newsletter = config('storefront.newsletter'))
<div class="rbt-newsletter-area style--one rbt-bg-color-primary">
    <div class="container">
        <div class="row row--12 mt_dec--24 align-items-center">
            <div class="col-lg-8 mt--24 justify-content-center justify-content-md-start d-flex">
                <div class="rbt-newsletter-content-wrapper">
                    <h2 class="title">{{ $newsletter['title'] }} <span>{{ $newsletter['highlight'] }}</span></h2>
                    <p class="sub-title">{{ $newsletter['subtitle'] }}</p>
                </div>
            </div>
            <div class="col-lg-4 mt--24 d-flex justify-content-center justify-content-md-end text-center text-md-left d-flex">
                <form action="#" class="rbt-newsletter-form-one rbt-max-w-full w-100 radius-round">
                    <input type="email" name="email" placeholder="Votre adresse e-mail" aria-label="Adresse e-mail">
                    <button type="submit" class="rbt-btn rbt-btn-md">
                        S’abonner
                    </button>
                    <div class="icon"><i class="fa-regular fa-envelope"></i></div>
                </form>
            </div>
        </div>
    </div>
</div>
