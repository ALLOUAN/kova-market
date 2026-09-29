<footer class="rbt-footer rbt-footer-style-one rbt-bg-color-gray-light">
    <div class="rbt-footer-top rbt-section-gap2Top">
        <div class="container">
            <div class="row justify-content-between row--12 mt_dec--24 pb--40 pb_sm--24">
                <div class="col-lg-4 col-md-6 col-sm-6 col-12 mt--24 border-end rbt-border-color-border-2">
                    <div class="footer-widget">
                        @include('partials.header.logo')
                        <p class="description pr--140 pr_sm--0">{{ config('storefront.about') }}</p>
                        <div class="rbt-quick-contact-info">
                            <p class="b2 title">Appel depuis un fixe ou un mobile.</p>
                            <a class="contact-link has-lg-fsize" href="{{ $contact['toll_free_href'] }}">{{ $contact['toll_free'] }}</a>
                        </div>
                        <div class="rbt-quick-contact-info">
                            <p class="b2 title">Horaires du service client</p>
                            <p class="text-inf">{{ $contact['opening_hours'] }}</p>
                        </div>
                        <div class="rbt-quick-contact-info d-flex rbt-gap--4 align-items-center">
                            <p class="b2 title mb--0">E-mail : </p>
                            <a class="contact-link" href="mailto:{{ $contact['email'] }}"> {{ $contact['email'] }}</a>
                        </div>
                    </div>
                </div>

                @foreach ($footerLinks as $title => $links)
                    <div class="col-lg-2 col-md-6 col-sm-6 col-12 mt--24">
                        <div class="footer-widget rbt-link-hover">
                            <h3 class="ft-title">{{ $title }}</h3>
                            <ul class="ft-link">
                                @foreach ($links as $link)
                                    <li>
                                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row pb--40 pb_sm--24">
                <div class="col-12">
                    <a href="#">
                        <img src="{{ asset(config('storefront.footer_banner')) }}" alt="{{ config('storefront.name') }}">
                    </a>
                </div>
            </div>

        </div>
    </div>
    <div class="rbt-separator-mid">
        <div class="container">
            <hr class="rbt-separator m-0">
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="row row--12 align-items-center mt_dec--24">
                <div class="col-lg-6 mt--24">
                    <div class="rbt-footer-social-area justify-content-center justify-content-lg-start">
                        @if ($socialLinks->isNotEmpty())
                        <p class="title">Suivez-nous :</p>
                        <ul class="social-icon social-icon-md rbt-social-default with-bg-primary justify-content-start justify-content-lg-end">
                            @foreach ($socialLinks as $network)
                                <li><a href="{{ $network['url'] }}" aria-label="{{ $network['icon'] }}"><i class="fa-brands {{ $network['icon'] }}"></i></a></li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
                <div class="col-lg-6 mt--20">
                    <div class="rbt-app-store-area justify-content-center justify-content-lg-end">
                        <p class="title">Téléchargez l’app :</p>
                        <ul class="rbt-app-store-list">
                            @foreach (config('storefront.app_stores') as $store)
                                <li><a href="{{ $store['url'] }}"><img src="{{ asset($store['image']) }}" alt="{{ $store['label'] }}"></a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<div class="copyright-area copyright-style-1">
    <div class="container">
        <div class="row row--12 align-items-center justify-content-between mt_dec--24">
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-12 col-12 mt--24">
                <p class="rbt-link-hover text-center text-lg-start">Copyright <span class="copyright-year">{{ now()->year }}</span>
                    &copy;
                    <a href="{{ route('home') }}" class="rbt-text-semi-bold rbt-text-color-heading">{{ config('storefront.name') }}</a>.
                </p>
            </div>
            <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-12 col-12 mt--24">
                <ul class="payment-img-link">
                    <li><a href="#"><img src="{{ asset(config('storefront.payment_methods_image')) }}" alt="Moyens de paiement acceptés"></a>
                    </li>
                </ul>
            </div>
            <div class="col-xxl-4 col-xl-4 col-lg-12 col-md-12 col-12 mt--24">
                <ul class="copyright-link rbt-link-hover justify-content-center justify-content-xl-end mt_sm--12 mt_md--12 mt_lg--12">
                    @foreach ($legalLinks as $link)
                        <li><a href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                    @if (app(\App\Services\Storefront\Analytics::class)->enabled())
                        <li><button type="button" class="btn btn-link p-0 border-0 align-baseline text-reset" data-cookie-settings>Gérer les cookies</button></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
