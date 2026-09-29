{{-- Consent banner (EX-21, F-156), shown by assets/js/analytics.js while the visitor has not chosen. --}}
<div class="rbt-cookies" data-cookie-banner role="dialog" aria-label="Cookies">
    <div class="rbt-icon">
        <img src="{{ asset('assets/images/icons/cookie.svg') }}" alt="">
    </div>
    <div class="rbt-content">
        <div class="rbt-cookie-info">
            <p class="b2 mb--4 rbt-text-bold rbt-text-color-heading">Nous respectons votre vie privée</p>
            <p class="b4 mb--0 rbt-text-color-gray-600">
                Avec votre accord, nous mesurons la fréquentation du site et l’efficacité de nos publicités (Google Analytics, Meta, TikTok).
                Sans accord, rien de tout cela n’est chargé. <a href="{{ route('pages.show', 'politique-de-confidentialite') }}" class="rbt-btn-link rbt-text-color-heading rbt-text-bold b4">Politique de confidentialité</a>
            </p>
        </div>
        <div class="rbt-gap--8 rbt-btn-group">
            <button type="button" class="rbt-btn rbt-btn-md rbt-btn-gray-light" data-cookie-decline>Refuser</button>
            <button type="button" class="rbt-btn rbt-btn-md" data-cookie-accept>Accepter</button>
        </div>
    </div>
    <button type="button" class="rbt-close-btn" data-cookie-decline aria-label="Refuser et fermer">
        <i class="fa-sharp fa-solid fa-xmark"></i>
    </button>
</div>
