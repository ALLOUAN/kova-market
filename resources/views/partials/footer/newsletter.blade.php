@php($newsletter = config('storefront.newsletter'))
{{-- Newsletter sign-up of the footer: answered in place, plain POST without JavaScript. --}}
<div class="rbt-newsletter-area style--one rbt-bg-color-primary" id="newsletter">
    <div class="container">
        <div class="row row--12 mt_dec--24 align-items-center">
            <div class="col-lg-7 mt--24 justify-content-center justify-content-md-start d-flex">
                <div class="rbt-newsletter-content-wrapper">
                    <h2 class="title">{{ $newsletter['title'] }} <span>{{ $newsletter['highlight'] }}</span></h2>
                    <p class="sub-title">{{ $newsletter['subtitle'] }}</p>
                </div>
            </div>
            <div class="col-lg-5 mt--24">
                @include('partials.newsletter-form', ['source' => 'footer', 'formClass' => 'rbt-newsletter-form-one rbt-max-w-full w-100 radius-round'])
            </div>
        </div>
    </div>
</div>
