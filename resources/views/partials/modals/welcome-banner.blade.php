{{-- Newsletter invitation window: opens once, after a while on the page, never to someone already signed up or who
     closed it (not the theme's "at once on every first visit": its id is not the theme's). Switched on and timed in
     Paramètres de la boutique › Newsletter. --}}
@php($newsletter = config('storefront.newsletter'))
<x-modal id="newsletterModal" dialog-class="modal-dialog-centered xs-size" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-welcome-banner-area rbt-content-trs-portion">
            <div class="rbt-welcome-banner-content">
                @if (filled($newsletter['popup_image'] ?? null))
                    <div class="rbt-welcome-banner-top position-relative overflow-hidden rbt-rounded--12">
                        <img src="{{ asset($newsletter['popup_image']) }}" alt="" loading="lazy" decoding="async">
                    </div>
                @endif
                <div class="rbt-welcome-banner-bottom pt--32">
                    <h2 class="text-center mb--12 h4" id="newsletterModalLabel">{{ $newsletter['popup_title'] }}</h2>
                    <p class="text-center b1 mb--24">{{ $newsletter['popup_text'] }}</p>
                    @include('partials.newsletter-form', ['source' => 'popup', 'formClass' => 'form-newsletter kova-newsletter-stacked'])
                    <div class="text-center mt--8">
                        <button type="button" data-bs-dismiss="modal" class="rbt-btn rbt-btn-naked radius-round-6 d-block w-100">Pas intéressé</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-modal>

@push('scripts')
    <script>
        (function () {
            var KEY = 'kova_newsletter';
            try { if (localStorage.getItem(KEY)) { return; } } catch (e) { return; }

            var element = document.getElementById('newsletterModal');
            if (!element || !window.bootstrap) { return; }

            // Closed or answered: never again on this browser.
            element.addEventListener('hidden.bs.modal', function () { try { localStorage.setItem(KEY, 'dismissed'); } catch (e) {} });

            setTimeout(function () {
                // Not over another window already open, nor after a message of the page (assets/js/flash.js).
                if (document.querySelector('.modal.show') || element.dataset.suppressed) { return; }
                bootstrap.Modal.getOrCreateInstance(element).show();
            }, {{ max(3, (int) ($newsletter['popup_delay'] ?? 15)) * 1000 }});
        })();
    </script>
@endpush
